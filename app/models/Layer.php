<?php
namespace app\models;
class Layer extends \app\models\Table
{
    function __construct()
    {
        parent::__construct("settings.geometry_columns_view");
    }

    public function getValueFromKey($_key_, $column)
    {
        $rows = $this->getRecords();
        $rows = $rows['data'];
        foreach ($rows as $row) {
            foreach ($row as $field => $value) {
                if ($field == "_key_" && $value == $_key_) {
                    return ($row[$column]);
                }
            }
        }
    }

    function getAll($schema = false, $auth)
    {
        $where = ($auth) ?
            "(authentication<>'foo')" :
            "(authentication='Write' OR authentication='None')";
        $sql = ($schema) ?
            "SELECT * FROM settings.geometry_columns_view WHERE {$where} AND _key_ LIKE '{$schema}%' order by sort_id" :
            "SELECT * FROM settings.geometry_columns_view WHERE {$where} order by sort_id";
        $result = $this->execQuery($sql);
        if (!$this->PDOerror) {
            while ($row = $this->fetchRow($result, "assoc")) {
                $arr = array();
                foreach ($row as $key => $value) {
                    $value = ($key == "layergroup" && (!$value))? "Default group":$value;
                    $arr = $this->array_push_assoc($arr, $key, $value);
                }
                $response['data'][] = $arr;
            }
            $response['data'] = ($response['data'])?:array();
        }
        if (!$this->PDOerror) {
            $response['success'] = true;
            $response['message'] = "geometry_columns_view fetched";
        } else {
            $response['success'] = false;
            $response['message'] = $this->PDOerror[0];
        }
        return $response;
    }

    function getSchemas() // All tables
    {
        $sql = "SELECT f_table_schema as schemas FROM settings.geometry_columns_view WHERE f_table_schema IS NOT NULL AND f_table_schema!='sqlapi' GROUP BY f_table_schema";
        $result = $this->execQuery($sql);
        if (!$this->PDOerror) {
            while ($row = $this->fetchRow($result, "assoc")) {
                $arr[] = array("schema" => $row["schemas"], "desc" => null);
            }
            $response['success'] = true;
            $response['data'] = $arr;
        } else {
            $response['success'] = false;
            $response['message'] = $this->PDOerror;
        }
        return $response;
    }

    function getCartoMobileSettings($_key_) // Only geometry tables
    {
        $response['success'] = true;
        $response['message'] = "Structure loaded";
        $arr = array();
        $keySplit = explode(".", $_key_);
        $table = new Table($keySplit[0] . "." . $keySplit[1]);
        $cartomobileArr = (array)json_decode($this->getValueFromKey($_key_, "cartomobile"));
        //print_r($cartomobileArr);
        foreach ($table->metaData as $key => $value) {
            if ($value['type'] != "geometry" && $key != $table->primeryKey['attname']) {
                $arr = $this->array_push_assoc($arr, "id", $key);
                $arr = $this->array_push_assoc($arr, "column", $key);
                $arr = $this->array_push_assoc($arr, "available", $cartomobileArr[$key]->available);
                $arr = $this->array_push_assoc($arr, "cartomobiletype", $cartomobileArr[$key]->cartomobiletype);
                $arr = $this->array_push_assoc($arr, "properties", $cartomobileArr[$key]->properties);
                if ($value['typeObj']['type'] == "decimal") {
                    $arr = $this->array_push_assoc($arr, "type", "{$value['typeObj']['type']} ({$value['typeObj']['precision']} {$value['typeObj']['scale']})");
                } else {
                    $arr = $this->array_push_assoc($arr, "type", "{$value['typeObj']['type']}");
                }
                $response['data'][] = $arr;
            }
        }
        return $response;
    }

    function updateCartoMobileSettings($data, $_key_)
    {
        $table = new Table("settings.geometry_columns_join");
        $data = $table->makeArray($data);
        $cartomobileArr = (array)json_decode($this->getValueFromKey($_key_, "cartomobile"));
        foreach ($data as $value) {
            $safeColumn = $table->toAscii($value->column, array(), "_");
            if ($value->id != $value->column && ($value->column) && ($value->id)) {
                unset($cartomobileArr[$value->id]);
            }
            $cartomobileArr[$safeColumn] = $value;
        }
        $conf['cartomobile'] = json_encode($cartomobileArr);
        $conf['_key_'] = $_key_;

        $table->updateRecord(json_decode(json_encode($conf)), "_key_");
        //$this->execQuery($sql, "PDO", "transaction");
        if ((!$this->PDOerror) || (!$sql)) {
            $response['success'] = true;
            $response['message'] = "Column renamed";
        } else {
            $response['success'] = false;
            $response['message'] = $this->PDOerror[0];
        }
        return $response;
    }

    private function array_push_assoc($array, $key, $value)
    {
        $array[$key] = $value;
        return $array;
    }

}
