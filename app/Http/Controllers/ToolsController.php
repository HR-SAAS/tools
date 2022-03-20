<?php

namespace App\Http\Controllers;

use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Application;
use Medoo\Medoo;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\TblWidth;


class ToolsController extends Controller
{


    public function db(Request $request)
    {
        $database = $request->get('database');
        $user = $request->get('username');
        $password = $request->get('password');
        $host = $request->get('host');

        $port = $request->get('port', 3306);
        if (!$database || !$user || !$password || !$host) {
            return $this->error('参数不能为空');
        }
        $database = new Medoo([
            // [required]
            'type' => 'mysql',
            'host' => $host,
            'database' => $database,
            'username' => $user,
            'password' => $password,
            // [optional]
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'port' => $port,
        ]);

        $res = $database->query('show tables')->fetchAll();
        $tables = [];
        foreach ($res as $item) {
            $tables[] = $item[0];
        }
        $dic = [
            'Field' => '列名',
            'Type' => '数据类型',
            'Length' => '长度',
            'Key' => '主键',
            'Comment' => '说明'
        ];
        $oneMil = 567;
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->addTableStyle('dbTable', [
            'borderColor' => '000000',
            'borderSize' => 6,
            'align' => 'center',
            'width' => $oneMil * 12.4,
            'unit' => TblWidth::TWIP
        ]);
        $cellCenter = ['align' => 'center'];
        foreach ($tables as $table) {
            $section = $phpWord->addSection();
            $section->addText($table . '表',null, $cellCenter);
            $docTable = $section->addTable('dbTable');
            $temp = $database->query('show full columns from ' . $table)->fetchAll();
            $docTable->addRow(25);
            foreach ($dic as $title) {
                $docTable->addCell(1196)->addText($title,null, $cellCenter);
            }
            foreach ($temp as $column) {
                $docTable->addRow(25);
                $docTable->addCell(1196, $cellCenter)->addText($column['Field']);
                $docTable->addCell(1196, $cellCenter)->addText(preg_replace('#\((\d+)\)#', '', $column['Type']));
                $length = [];
                preg_match('#\((\d+)\)#', $column['Type'], $length);
                if (count($length) > 1) {
                    $length = $length[1];
                } else {
                    $length = '';
                }
                $docTable->addCell(1196, $cellCenter)->addText($length,null, $cellCenter);
                $docTable->addCell(1196, $cellCenter)->addText($column['Key'] === 'PRI' ? '是' : '否',null, $cellCenter);
                $docTable->addCell(1196, $cellCenter)->addText($column['Comment']);
            }
        }
        $disk = Storage::disk('public');
        //查询结构
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

        $fileName =sprintf('db_word_%s_%s.docx',date('Ymd_His',time()),md5($host));
        $objWriter->save($disk->path($fileName));
        return response()->download($disk->path($fileName))->deleteFileAfterSend(true);
    }
}
