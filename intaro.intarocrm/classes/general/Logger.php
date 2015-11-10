<?php
class Logger
{
    private $logPath;
    private $files;
    
    public function __construct($logPath = '/bitrix/modules/intaro.intarocrm/classes/general/log', $files = 3)
    {
        $this->logPath = $logPath;
        $this->files = $files;
    }
    
    public function write($dump, $file = 'info')
    {
        $file = $_SERVER["DOCUMENT_ROOT"] . $this->logPath . '/' . $file . '.log';
        
        // message prefix with current time
        $data['TIME'] = date('Y-m-d H:i:s');
        $data['DATA'] = $dump;

        //write log
        $f = fopen($file, "a+");
        fwrite($f, print_r($data,true));
        fclose($f); 

        // if filesize more than 5 Mb rotate it
        if (filesize($file) > 5242880) {
            $this->rotate($file);
        } 
    }

    private function rotate($file)
    {
        $path = pathinfo($file);
        $rotate = implode('', array(
            $path['dirname'],
            '/',
            $path['filename'],
            date('YmdHis'),
            '.',
            $path['extension']
        ));

        copy($file, $rotate);
        $this->clean($file);

        $files = glob($path['dirname'] . '/' . "*" . $path['filename'] . ".log");

        if (0 === $this->files) {
            return;
        }

        if (count($files) > $this->files) {
            natsort($files);
            foreach (array_slice($files, $this->files) as $log) {
                if (is_writable($log)) {
                    unlink($log);
                }
            }
        }
    }

    private function clean($file)
    {
        file_put_contents($file, '');
    }

}
