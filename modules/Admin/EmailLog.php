<?php

class Ccheckin_Admin_EmailLog extends Bss_ActiveRecord_Base
{
    
    public static function SchemaInfo ()
    {
        return array(
            '__type' => 'ccheckin_email_log',
            '__pk' => array('id'),
            
            'id' => 'int',          
            'type' => 'string',         
            'creationDate' => array('datetime', 'nativeName' => 'creation_date'),
            'recipients' => 'string',
            'subject' => 'string',
            'body' => 'string',
            'attachments' => 'string',
            'success' => 'bool',
        );
    }

    public function getRecipients ()
    {
        return explode(',', $this->_fetch('recipients'));
    }

    public function setAttachments ($attachments)
    {
        $atts = array();
        foreach ($attachments as $att)
        {
            $atts[] = $att->id;
        }
        $this->_assign('attachments', (string)implode(',', $atts));
    }
    public function getAttachments ()
    {
        $attIds = explode(',', $this->_fetch('attachments'));
        $files = $this->getSchema('Ccheckin_Admin_File');
        
        return $files->find($files->inList($attIds));
    }
}