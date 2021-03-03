<?php

namespace Intaro\RetailCrm\Icml;


use Intaro\RetailCrm\Icml\IcmlWriter;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;

/**
 * Class RetailCrmXml
 */
class RetailCrmXml
{
    public const INFO = 'INFO';
    /**
     * @var IcmlWriter
     */
    private $icmlWriter;
    
    /** @var icmlDataManager */
    private $icmlDataManager;
    
    /**
     * RetailCrmlXml constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->icmlWriter = new IcmlWriter();
        $this->icmlDataManager = new IcmlDataManager($setup, $this->icmlWriter);
    }
    
    public function generateXml(): void
    {
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': Start getting data for XML', self::INFO);
        $data = $this->icmlDataManager->getXmlData();
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': End getting data for XML and Start writing categories and header', self::INFO);
        $this->icmlWriter->writeToXmlHeaderAndCategories($data);
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': End writing categories in XML and Start writing offers', self::INFO);
        $this->icmlDataManager->writeOffersHandler();
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': End writing offers in XML', self::INFO);
        $this->icmlWriter->writeToXmlBottom();
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': Loading complete (peek memory usage: ' . memory_get_peak_usage() . ')', self::INFO);
    }
}
