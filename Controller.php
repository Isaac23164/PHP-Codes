<?php
declare(encoding = "utf-8");

require_once 'ForwardFW/Interface/Application.php';

class ForwardFW_Controller
{
    /**
     * The application object.
     *
     * @var ForwardFW_Interface_Application
     */
    protected $application;

    /**
     * Constructor
     *
     * @param ForwardFW_Interface_Application $application The running application.
     *
     * @return void
     */
    public function __construct(ForwardFW_Interface_Application $application)
    {
        $this->application = $application;
    }

    /**
     * Returns content of the given parameter for this class.
     *
     * @param string $strParameterName Name of parameter.
     *
     * @return mixed
     */
    function getParameter($strParameterName)
    {
        return $this->application->getRequest()->getParameter(
            $strParameterName,
            get_class($this),
            $this->application->getName()
        );
    }

    /**
     * Returns configuration of the given parameter for this class.
     *
     * @param string $strParameterName Name of parameter.
     *
     * @return mixed
     */
    function getConfigParameter($strParameterName)
    {
        return $this->application->getRequest()->getConfigParameter(
            $strParameterName,
            get_class($this),
            $this->application->getName()
        );
    }
}