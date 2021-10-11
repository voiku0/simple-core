<?php

namespace SimpleCore;
use SimpleCore\DtAbstract;
use SimpleCore\Exception\InvalidDbSetup;

abstract class DbAbstract
{
  protected array $_full_select_columns = array();
  protected array $_insert_columns = array();

  protected array $_fields_arr = array();
  protected ?string $_primary_key = null;
  protected ?string $_table_name = null;
  protected ?DtAbstract $_dt_type = null;
  protected ?int $_is_deleted = null;
  protected ?string $_order_by = null;
  protected $_connection = null;

  /**
   * DbAbstract constructor.
   */
  public function __construct($_connection = null)
  {
    $this->_full_select_columns = array_keys($this->_fields_arr);
    foreach ($this->_fields_arr as $key => $value) {
      //primary key is not used in insert
      if ($key == $this->_primary_key) {
        continue;
      }
      //we don't populate the deleted forms
      if (strstr($key,'delete')) {
        continue;
      }
      $this->_insert_columns[] = $key;
    }
    if ($this->_is_deleted === null) {
      $this->_is_deleted = $this->_table_name.'_is_deleted';
    }
    $this->_connection = $_connection;
    if ($this->_connection == null) {
      $this->_connection = Adaptor\Db::getInstance();
    }
  }

  protected function checkObject($obj) {
    if (!is_a($obj, $this->_dt_type::class)) {
      throw new InvalidDbSetup('check the object you are using for calling this. Got:'.get_class($obj).' expected:'.$this->_dt_type);
    }
  }

  /**
   * @param $obj
   * @return int
   * @throws InvalidDbSetup
   */
  public function add($obj): int {
    $this->checkObject($obj);
    $bindArray = array();
    foreach ($this->_insert_columns as $column) {
      $bindArray[':'.$column] = [
        'type' => $this->_fields_arr[$column],
        'value' => $obj->$column===\Jules\Db\SQLBuilder::DB_NULL?null:$obj->$column
      ];
    }
    $id = $this->_connection->insert(
      'INSERT INTO `'.$this->_table_name.'`
      (`'.implode('`,`', $this->_insert_columns).'`)
      VALUES
      (:'.implode(',:',$this->_insert_columns).')',
      $bindArray
    );
    DbWrites::getInstance()->registerTable($this->_table_name);
    return intval($id);
  }

  public function save($obj) {
    $this->checkObject($obj);
    if (intval($obj->{$this->_primary_key}) <= 0) {
      throw new \Jules\Exception\InternalError('Invalid Db call');
    }
    $sql = 'UPDATE `'.$this->_table_name.'` SET ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->update($this->_fields_arr, $obj, [$this->_primary_key, $this->_is_deleted]);
    if (count($sqlBuilder->getSet()->toArray()) && !is_null($sqlBuilder->getCondition())) {
      $sql .= $sqlBuilder->getSet()->toString() . ' WHERE '.$sqlBuilder->getCondition()->toString();
      $this->_connection->insert($sql, $sqlBuilder->getBind()->toArray());
    }
    DbWrites::getInstance()->registerTable($this->_table_name);
  }

  public function saveMultiple($obj, array $whereFields) {
    $this->checkObject($obj);

    $sql = 'UPDATE `'.$this->_table_name.'` SET';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->update($this->_fields_arr, $obj, $whereFields);
    if (count($sqlBuilder->getSet()->toArray()) && !is_null($sqlBuilder->getCondition())) {
      $sql .= $sqlBuilder->getSet()->toString() . ' WHERE '.$sqlBuilder->getCondition()->toString();
      $this->_connection->insert($sql, $sqlBuilder->getBind()->toArray());
    }
    DbWrites::getInstance()->registerTable($this->_table_name);
    return true;
  }


  public function get($value) {
    $stmt = $this->_connection->execute('SELECT `'.implode('`,`', $this->_full_select_columns).'`
        FROM `'.$this->_table_name.'` '
      .'WHERE `'.$this->_primary_key.'` = :'.$this->_primary_key
      .' AND `'.$this->_is_deleted.'` = :'.$this->_is_deleted
      .($this->_order_by === null ? '' : ' ORDER BY '.$this->_order_by),
      array(
        ':'.$this->_primary_key => $value,
        ':'.$this->_is_deleted => 0
      ),
      DbWrites::getInstance()->wasWrite($this->_table_name)
    );

    return $stmt->fetch(\PDO::FETCH_OBJ);
  }

  public function getAll($obj): array {
    $this->checkObject($obj);
    $sql = 'SELECT `'.implode('`,`', $this->_full_select_columns).'`
        FROM `'.$this->_table_name.'` ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->getAll($this->_fields_arr, $obj);
    if ($sqlBuilder->getCondition()->toString() !== null) {
      $sql .= ' WHERE '.$sqlBuilder->getCondition()->toString();
    }
    if ($this->_order_by !== null) {
      $sql .= ' ORDER BY '.$this->_order_by;
    }

    if ($obj instanceof DtAbstract && $obj->getLimit() !== null) {
      $sql .= ' LIMIT ';
      if ($obj->getOffset() !== null) {
        $sql .= $obj->getOffset() . ', ';
      }
      $sql .= $obj->getLimit();
    }
    $stmt = $this->_connection->execute($sql, $sqlBuilder->getBind()->toArray(), DbWrites::getInstance()->wasWrite($this->_table_name));
    return $stmt->fetchAll(\PDO::FETCH_OBJ);
  }

  public function getAllBatch($obj, $limit = null, $offset = null): \PDOStatement {
    $this->checkObject($obj);
    $sql = 'SELECT `'.implode('`,`', $this->_full_select_columns).'`
        FROM `'.$this->_table_name.'` ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->getAll($this->_fields_arr, $obj);
    if ($sqlBuilder->getCondition()->toString() !== null) {
      $sql .= ' WHERE '.$sqlBuilder->getCondition()->toString();
    }
    if ($this->_order_by !== null) {
      $sql .= ' ORDER BY '.$this->_order_by;
    }
    if ($limit !== null) {
      $sql .= ' LIMIT ';
      if ($offset !== null) {
        $sql .= $offset . ', ';
      }
      $sql .= $limit;
    }
    $stmt = $this->_connection->execute($sql, $sqlBuilder->getBind()->toArray(), DbWrites::getInstance()->wasWrite($this->_table_name));
    return $stmt;
  }

  public function getCount($obj) {
    $this->checkObject($obj);
    $sql = 'SELECT count(*) AS `count`
        FROM `'.$this->_table_name.'` ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->getAll($this->_fields_arr, $obj);
    if ($sqlBuilder->getCondition()->toString() !== null) {
      $sql .= ' WHERE '.$sqlBuilder->getCondition()->toString();
    }
    if ($this->_order_by !== null) {
      $sql .= ' ORDER BY '.$this->_order_by;
    }
    $stmt = $this->_connection->execute($sql, $sqlBuilder->getBind()->toArray(), DbWrites::getInstance()->wasWrite($this->_table_name));
    return $stmt->fetch(\PDO::FETCH_OBJ)->count;
  }

  public function delete($obj) {
    $this->checkObject($obj);
    if (intval($obj->{$this->_primary_key}) <= 0) {
      throw new \Jules\Exception\InternalError('Invalid Db call');
    }
    $sql = 'UPDATE `'.$this->_table_name.'` SET ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->update($this->_fields_arr, $obj, [$this->_primary_key]);
    if (count($sqlBuilder->getSet()->toArray()) && !is_null($sqlBuilder->getCondition())) {
      $sql .= $sqlBuilder->getSet()->toString() . ' WHERE '.$sqlBuilder->getCondition()->toString();
      $this->_connection->insert($sql, $sqlBuilder->getBind()->toArray());
    }
    DbWrites::getInstance()->registerTable($this->_table_name);
  }

  public function deleteMultiple($obj, array $whereFields) {
    $this->checkObject($obj);

    $sql = 'UPDATE `'.$this->_table_name.'` SET';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->update($this->_fields_arr, $obj, $whereFields);
    if (count($sqlBuilder->getSet()->toArray()) && !is_null($sqlBuilder->getCondition())) {
      $sql .= $sqlBuilder->getSet()->toString() . ' WHERE '.$sqlBuilder->getCondition()->toString();
      $this->_connection->insert($sql, $sqlBuilder->getBind()->toArray());
    }
    DbWrites::getInstance()->registerTable($this->_table_name);
    return true;
  }

  public function purge($obj) {
    $this->checkObject($obj);
    if (intval($obj->{$this->_primary_key}) <= 0) {
      throw new \Jules\Exception\InternalError('Invalid Db call');
    }

    $sql = 'DELETE FROM `'.$this->_table_name.'` WHERE ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->getAll($this->_fields_arr, $obj);
    if ($sqlBuilder->getCondition()->toString() !== null) {
      $sql.= ' '.$sqlBuilder->getCondition()->toString();
    } else {
      return false;
    }

    $stmt = $this->_connection->execute($sql, $sqlBuilder->getBind()->toArray());
    DbWrites::getInstance()->registerTable($this->_table_name);
    return true;
  }

  public function purgeMultiple($obj) {
    $this->checkObject($obj);

    $sql = 'DELETE FROM `'.$this->_table_name.'` ';
    $sqlBuilder = new SQLBuilder();
    $sqlBuilder->getAll($this->_fields_arr, $obj);
    if ($sqlBuilder->getCondition()->toString() !== null) {
      $sql.= ' WHERE '.$sqlBuilder->getCondition()->toString();
    } else {
      return false;
    }

    $stmt = $this->_connection->execute($sql, $sqlBuilder->getBind()->toArray());
    DbWrites::getInstance()->registerTable($this->_table_name);
    return true;
  }

  public function setOrderBy($value) {
    if ($value !== null) {
      $this->_order_by = $value;
    }
    return $this;
  }
}
