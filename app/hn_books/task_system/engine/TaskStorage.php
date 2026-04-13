<?php
declare(strict_types=1);

final class TaskStorage
{

    private static function baseDir(): string
    {
        return dirname(__DIR__)."/data";
    }


    private static function sanitize(string $value): string
    {
        return preg_replace('/[^a-z0-9_\-]/i','_',$value);
    }


    private static function path(string $book,string $text): string
    {
        $book = self::sanitize($book);
        $text = self::sanitize($text);

        return self::baseDir()."/{$book}/{$text}.json";
    }


    /*
    --------------------------------------------------
    SAVE
    --------------------------------------------------
    */

    public static function save(string $book,string $text,array $tasks,array $meta=[]): void
    {

        $dir = self::baseDir()."/".self::sanitize($book);

        if(!is_dir($dir)){

            if(!mkdir($dir,0775,true) && !is_dir($dir)){
                throw new RuntimeException("Kunne ikke opprette mappe: $dir");
            }

        }

        $file = self::path($book,$text);

        $payload = [

            "meta"=>array_merge([
                "generated_at"=>date("c"),
                "task_count"=>count($tasks)
            ],$meta),

            "tasks"=>$tasks

        ];

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        if($json === false){
            throw new RuntimeException("JSON encode feilet");
        }

        /*
        atomisk skriving
        */

        $tmp = $file.".tmp";

        file_put_contents($tmp,$json);

        rename($tmp,$file);

    }


    /*
    --------------------------------------------------
    LOAD
    --------------------------------------------------
    */

    public static function load(string $book,string $text): array
    {

        $file = self::path($book,$text);

        if(!file_exists($file)){
            return [];
        }

        $data = json_decode(file_get_contents($file),true);

        if(!$data){
            return [];
        }

        return $data["tasks"] ?? [];

    }


    /*
    --------------------------------------------------
    LOAD FULL FILE
    --------------------------------------------------
    */

    public static function loadFull(string $book,string $text): array
    {

        $file = self::path($book,$text);

        if(!file_exists($file)){
            return [];
        }

        $data = json_decode(file_get_contents($file),true);

        return $data ?? [];

    }


    /*
    --------------------------------------------------
    APPEND TASKS
    --------------------------------------------------
    */

    public static function append(string $book,string $text,array $tasks): void
    {

        $existing = self::load($book,$text);

        $merged = array_merge($existing,$tasks);

        self::save($book,$text,$merged);

    }


    /*
    --------------------------------------------------
    DELETE
    --------------------------------------------------
    */

    public static function delete(string $book,string $text): void
    {

        $file = self::path($book,$text);

        if(file_exists($file)){
            unlink($file);
        }

    }

}