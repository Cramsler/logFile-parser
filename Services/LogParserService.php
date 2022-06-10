<?php

class LogParserService
{

    private string $file;
    private array $output;
    private int $counter = 0;
    private array $usersIpAddresses = [];
    private array $httpStatuses = [];

    private const PARSE_PATTERN = '/(\S+) (\S+) (\S+) \[(.+?)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) \"(.*?)\" \"(.*?)\"/';

    public function __construct(string $file)
    {
        $this->file = $file;
        $this->output = $this->getOutputPattern();
    }

    /**
     * Returns the structure of the desired output as an array
     *
     * @return array
     */
    private function getOutputPattern(): array
    {
        return [
            'views'    => 0,
            'urls'     => 0,
            'traffic'  => 0,
            'crawlers' => [
                'Google' => 0,
                'Yandex' => 0,
                'Bing'   => 0,
                'Baidu'  => 0,
            ],
            'status_codes' => [],
        ];
    }

    /**
     * Returns an array with log data
     *
     * @return array
     */
    public function readFile(): array
    {
        $file = $this->openFile();

        while(!feof($file))
        {
            $line = fgets($file);
            $this->counter++;

            if (preg_match(self::PARSE_PATTERN, trim($line), $matches))
            {
                $this->parseData($matches);
            }

            echo sprintf("Обработанно %s строк\r", $this->counter);
        }

        fclose($file);

        return $this->output;
    }

    /**
     * Trying to read the file. If that fails, it kills the script with an error message
     *
     */
    private function openFile()
    {
        $file = fopen($this->file, 'r');
        
        if($file)
        {
            return $file;
        } else {
            echo 'Не удалось прочитать файл, проверьте правильность имени файла';
            die;
        }
    }

    /**
     * Sets the desired values in the fields of the final object
     *
     */
    private function parseData(array $matches)
    {
        $userIp     = $matches[1];
        $httpStatus = $matches[8];
        $usingBytes = $matches[9];
        $browser    = $matches[11];

        if (!array_search($userIp, $this->usersIpAddresses))
        {
            $this->usersIpAddresses[] = $userIp;
        }

        !array_key_exists($httpStatus, $this->httpStatuses) ? $this->httpStatuses[$httpStatus] = 1 : $this->httpStatuses[$httpStatus] ++;

        $this->output['views']        = $this->counter;
        $this->output['urls']         = count($this->usersIpAddresses);
        $this->output['traffic']     += (int)$usingBytes;
        $this->output['status_codes'] = $this->httpStatuses;

        $botsPattern = "/bot|google|yandex|bing|baidu/i";

        preg_match($botsPattern, $browser, $botResult);

        if (!empty($botResult))
        {
            $browser = $botResult[0];

            !array_key_exists($browser, $this->output['crawlers']) ?  $this->output['crawlers'][$browser] = 1 :  $this->output['crawlers'][$browser]++;
        }
    }

}