<?php


namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class ApiBuildCommand extends Command
{
    protected function configure()
    {
        $this->setName('api-build')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->addOption('module', null, Option::VALUE_REQUIRED, 'module')
            ->addOption('replace', null, Option::VALUE_REQUIRED, 'replace')
            ->setDescription('自动生成Api相关的控制器、模型、验证器文件');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));
        if(!$name){
            $output->error('Error: Name 必填');
        }


        if ($input->hasOption('module')) {
            $module = $input->getOption('module');
        } else {
            $output->error('Error: 模块名 必填');
        }

        if ($input->hasOption('replace')) {
            $replace = $input->getOption('replace');
            $replace = $replace == 'true' ? true : false;
        } else {
            $replace = false;
        }

        $root_path = env('root_path');
        $app_path = env('app_path');
        $module_path = $app_path.$module.'/';
        $build_api_path = $root_path.'build/api/';
        $model_file = $app_path.'common/model/'.ucfirst(camelize($name)).'Model.php';
        $controller_file = $module_path.'controller/'.ucfirst(camelize($name)).'Controller.php';
        $validate_file = $module_path.'validate/'.ucfirst(camelize($name)).'Validate.php';

        if((!$replace) && file_exists($model_file)){
            $output->error('Error: Model 已存在需要替换请传递参数 replace true 值');
        }

        //模块不存在
        if(!is_dir($module_path)){
            $output->error('Error: 模块 '.$module.' 目录不存在');
        }

        //写入 Model
        $ModelContent = file_get_contents($build_api_path.'model.txt');
        //替换以下变量
        $ModelContent = $this->varReplace($name,$module,$ModelContent);
        //目录不存在
        if(!is_dir(dirname($model_file))){
            mkdir(dirname($model_file),0755);
        }
        file_put_contents($model_file,$ModelContent);

        //写入 Controller
        $ControllerContent = file_get_contents($build_api_path.'controller.txt');
        //替换以下变量
        $ControllerContent = $this->varReplace($name,$module,$ControllerContent);
        //目录不存在
        if(!is_dir(dirname($controller_file))){
            mkdir(dirname($controller_file),0755);
        }
        file_put_contents($controller_file,$ControllerContent);

        //写入 Validate
        $ValidateContent = file_get_contents($build_api_path.'validate.txt');
        //替换以下变量
        $ValidateContent = $this->varReplace($name,$module,$ValidateContent);

        //目录不存在
        if(!is_dir(dirname($validate_file))){
            mkdir(dirname($validate_file),0755);
        }

        file_put_contents($validate_file,$ValidateContent);

        $output->info('success');
    }


    protected function varReplace($name,$module,$content = ''){
        return str_ireplace(
            ['{{$Module|strtolower}}','{{$Name|camelize}}','{{$Name|camelize|ucfirst}}','{{$Name|strtolower}}',],
            [strtolower($module),camelize($name),ucfirst(camelize($name)),strtolower($name)],
            $content
        );
    }

}