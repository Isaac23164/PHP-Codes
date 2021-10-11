<?php
declare(encoding = "utf-8");

interface ForwardFW_Interface_Templater
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
     * Sets file to use for templating
     *
     * @param string $_strFile Complete path and filename.
     *
     * @return ForwardFW_Interface_Templater The instance.
     */
    public function setTemplateFile($_strFile);

    /**
     * Sets a var in the template to a value
     *
     * @param string $_strName Name of template var.
     * @param mixed  $_mValue  Value of template var.
     *
     * @return ForwardFW_Interface_Templater The instance.
     */
    public function setVar($_strName, $_mValue);

    /**
     * Returns compiled template for outputing.
     *
     * @return string Content of template after compiling.
     */
    public function getCompiled();

    public function defineBlock($strBlockName);

    public function showBlock($strBlockName);

    public function hideBlock($strBlockName);

}
?>