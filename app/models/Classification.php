<?php

namespace app\models;

use app\inc\Util;

class Classification extends \app\inc\Model
{
    private $table;

    function __construct($table)
    {
        parent::__construct();
        $this->table = $table;
    }

    private function array_push_assoc($array, $key, $value)
    {
        $array[$key] = $value;
        return $array;
    }

    public function getAll()
    {
        $sql = "SELECT class FROM settings.geometry_columns_join WHERE _key_='{$this->table}'";
        $result = $this->execQuery($sql);
        if (!$this->PDOerror) {
            $sortedArr = array();
            $response['success'] = true;
            $row = $this->fetchRow($result, "assoc");
            $arr = $arr2 = (array)json_decode($row['class']);
            for ($i = 0; $i < sizeof($arr); $i++) {
                $last = 100;
                foreach ($arr2 as $key => $value) {
                    if ($value->sortid < $last) {
                        $temp = $value;
                        $del = $key;
                        $last = $value->sortid;
                    }
                }
                array_push($sortedArr, $temp);
                unset($arr2[$del]);
                //print_r($arr2);
                $temp = null;
            }
            //$arr = $sortedArr;
            //print_r($arr);
            for ($i = 0; $i < sizeof($arr); $i++) {
                $arrNew[$i] = (array)Util::casttoclass('stdClass', $arr[$i]);
                $arrNew[$i]['id'] = $i;
            }
            $response['data'] = $arrNew;
        } else {
            $response['success'] = false;
            $response['message'] = $this->PDOerror[0];
        }
        return $response;
    }

    public function get($id)
    {
        $classes = $this->getAll();
        if (!$this->PDOerror) {
            $response['success'] = true;
            $arr = $classes['data'][$id];
            unset($arr['id']);
            $props = array(
                "name" => "Unnamed Class",
                "expression" => "",
                "label" => false,
                "force_label" => false,
                "color" => "#FF0000",
                "outlinecolor" => "#FF0000",
                "symbol" => "",
                "size" => "2",
                "width" => "1");
            foreach ($classes['data'][$id] as $value) {
                foreach ($props as $key2 => $value2) {
                    if (!isset($arr[$key2])) {
                        $arr[$key2] = $value2;
                    }
                }
            }
            $response['data'] = array($arr);
        } else {
            $response['success'] = false;
            $response['message'] = $this->PDOerror[0];
        }
        return $response;
    }

    public function store($data)
    {

        // First we replace unicode escape sequence
        //$data = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $data);
        $tableObj = new Table("settings.geometry_columns_join");
        $obj = new \stdClass;
        $obj->class = $data;
        $obj->_key_ = $this->table;
        $tableObj->updateRecord($obj, "_key_");
        if (!$tableObj->PDOerror) {
            $response['success'] = true;
            $response['message'] = "Class updated";
        } else {
            $response['success'] = false;
            $response['message'] = $tableObj->PDOerror[0];
        }
        return $response;
    }

    public function insert()
    {
        $classes = $this->getAll();
        $classes['data'][] = array("name" => "Unnamed class");
        $response = $this->store(json_encode($classes['data']));
        return $response;
    }

    public function update($id, $data)
    {
        $data->expression = urldecode($data->expression);
        $classes = $this->getAll();
        $classes['data'][$id] = $data;
        $response = $this->store(json_encode($classes['data']));
        return $response;
    }

    public function destroy($id) // Geometry columns
    {
        $classes = $this->getAll();
        unset($classes['data'][$id]);
        foreach ($classes['data'] as $key => $value) { // Reindex array
            unset($value['id']);
            $arr[] = $value;
        }
        $classes['data'] = $arr;
        //print_r($classes);
        $response = $this->store(json_encode($classes['data']));
        return $response;
    }

    static function createClass($type)
    {
        $symbol = "";
        $size = "2";
        $width = "2";
        $color = Util::randHexColor();
        if ($type == "POINT") {
            $symbol = "circle";
            $size = "10";
            $width = "1";
        }
        $jsonStr = '{"name":"Unnamed class","expression":"","label":false,"label_size":"","color":"' . $color . '","outlinecolor":"#000000","symbol":"' . $symbol . '","size":"' . $size . '","width":"' . $width . '","overlaycolor":"","overlayoutlinecolor":"","overlaysymbol":"","overlaysize":"","overlaywidth":""}';
        return json_decode($jsonStr);
    }


}