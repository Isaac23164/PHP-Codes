<?php
declare(encoding = "utf-8");

interface ForwardFW_Interface_DataHandler
{
    /**
     * Constructor
     *
     * @param ForwardFW_Interface_Application $application The running application
     *
     * @return void
     */
    public function __construct(
        ForwardFW_Interface_Application $application
    );

    /**
     * Calls the loading if it isn't in cache or cache timed out.
     *
     * @param string  $strConnection Name of connection defined in conf.
     * @param array   $arOptions     Operations for this load.
     * @param integer $nCacheTimeout Cache lifetime, -1 to use default.
     *
     * @return mixed The response Data
     */
    public function loadFromCached(
        $strConnection, array $arOptions, $nCacheTimeout = -1
    );

    /**
     * Load method.
     *
     * @param string $strConnection Name of connection defined in conf.
     * @param array  $arOptions     Operations for this load.
     *
     * @return mixed The response Data
     */
    public function loadFrom($strConnection, array $arOptions);

    /**
     * Save method.
     *
     * @param string $strConnection Name of connection defined in conf.
     * @param array  $options       Operations for the saving.
     *
     * @return boolean 
     */
    public function saveTo($strConnection, array $options);

    /**
     * Initialize the given connection.
     *
     * @param string $strConnection Name of connection defined in conf.
     *
     * @return void
     */
    public function initConnection($strConnection);
}
?>