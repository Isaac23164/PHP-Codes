<?php
declare(encoding = "utf-8");

class ForwardFW_Response
{
    /**
     * Holds every Log message as string.
     *
     * @var ForwardFW_Object_Timer
     */
    private $logTimer = null;

    /**
     * Holds every Error message as string.
     *
     * @var ForwardFW_Object_Timer
     */
    private $errorTimer = null;
    
    /**
     * Holds the content to send back to web server.
     *
     * @var string
     */
    private $strContent = '';

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->logTimer   = new ForwardFW_Object_Timer();
        $this->errorTimer = clone $this->logTimer;
    }

    /**
     * Adds an entry to the log array.
     *
     * @param string $strEntry The entry as string.
     *
     * @return ForwardFW_Response Themself.
     */
    public function addLog($strEntry)
    {
        $this->logTimer->addEntry($strEntry);
        return $this;
    }

    /**
     * Adds an entry to the error array.
     *
     * @param string $strEntry The entry as string.
     *
     * @return ForwardFW_Response Themself.
     */
    public function addError($strEntry)
    {
        $this->errorTimer->addEntry($strEntry);
        return $this;
    }

    /**
     * Adds a string to the existent content string.
     *
     * @param string $strContent The content as string.
     *
     * @return ForwardFW_Response Themself.
     */
    public function addContent($strContent)
    {
        $this->content .= $strContent;
        return $this;
    }

    /**
     * Returns the array with all its log entries.
     *
     * @return ForwardFW_Object_Timer The entries in a Timer Object.
     */
    public function getErrors()
    {
        return $this->errorTimer;
    }

    /**
     * Returns the array with all its log entries.
     *
     * @return ForwardFW_Object_Timer The entries in a Timer Object.
     */
    public function getLogs()
    {
        return $this->logTimer;
    }

    /**
     * Returns the content, which should be send back to web server.
     *
     * @return string The content.
     */
    public function getContent()
    {
        return $this->content;
    }
}

?>