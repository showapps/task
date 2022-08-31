<?php


namespace Tools;


class Responses
{

    static public function data($status,$message,$data = [],$params = [])
    {

        $responses = [];
        $responses['status'] = $status;
        $responses['message'] = $message;
        $responses['data'] = $data;
        $responses['params'] = $params;

        return json($responses,200);
    }





}