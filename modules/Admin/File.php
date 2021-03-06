<?php

class Ccheckin_Admin_File extends Bss_ActiveRecord_Base
{
    
    public static function SchemaInfo ()
    {
        return array(
            '__type' => 'ccheckin_admin_files',
            '__pk' => array('id'),
            
            'id' => 'int',
            'remoteName' => array('string', 'nativeName' => 'remote_name'),
            'localName' => array('string', 'nativeName' => 'local_name'),
            'contentType' => array('string', 'nativeName' => 'content_type'),
            'contentLength' => array('int', 'nativeName' => 'content_length'),
            'hash' => 'string',
            'title' => 'string',
            'temporary' => 'bool',

            // comma-separated list of email key names for which this file is used as an attachment
            'attachedEmailKeys' => array('string', 'nativeName' => 'attached_email_keys'),  
            
            'uploadedBy' => array('1:1', 'to' => 'Bss_AuthN_Account', 'keyMap' => array('uploaded_by_id' => 'id')),
            'uploadedDate' => array('datetime', 'nativeName' => 'uploaded_date'),
        );
    }

    protected function initialize ()
    {
        $this->addEventHandler('before-delete', array($this, 'beforeDelete'));
    }

    public function exists ()
    {
        return file_exists($this->localName);
    }

    public function getAttachedEmailKeys ()
    {
        return explode(',', $this->_fetch('attachedEmailKeys'));
    }

    // TODO: Add some functionality to add on extra keys to already existing ones if doesn't exist already
    public function setAttachedEmailKeys ($emailKeys)
    {
        if (is_array($emailKeys))
        {
            $this->_assign('attachedEmailKeys', implode(',', $emailKeys));
        }
        else
        {
            $this->_assign('attachedEmailKeys', implode(',', $emailKeys));
        }
        // if ($emailKey !== '' && $emailKey !== null)
        // {
        //     $keys = $this->attachedEmailKeys;
        //     $keys[] = $emailKey;
        //     $this->_assign('attachedEmailKeys', implode(',', $keys));
        // }
    }

    public function createFromRequest ($request, $inputName)
    {
        if ($file = $request->getFileUpload($inputName))
        {
            if ($file->isValid())
            {
                $antiVirus = $this->getApplication()->antiVirusManager;
                
                if (($messages = $antiVirus->scanFile($file->getLocalPath())) === null)
                {
                    $this->_assign('hash', $file->getHash());
                    if ($localName = $this->getLocalFilename(true))
                    {
                        if ($fileData = $request->getPostParameter('file'))
                        {
                            if (!empty($fileData['title']) && $fileData['title'] !== '')
                            {
                                $this->_assign('title', $fileData['title']);
                            }
                        }
                        else
                        {
                            $this->_assign('title', $file->getRemoteName());
                        }
                        $this->_assign('localName', $localName);
                        $this->_assign('remoteName', $file->getRemoteName());
                        $this->_assign('contentType', $file->getContentType());
                        $this->_assign('contentLength', $file->getContentLength());
                        $this->_assign('temporary', true);
                        $this->uploadedDate = new DateTime;
                        
                        $file->move($localName);
                    }
                    else
                    {
                        $this->invalidate($inputName, 'Could not create temporay directories for file.');
                    }
                }
                elseif ($antiVirus->isFileInfected($file->getLocalPath()))
                {
                    $antiVirus->setInfectedFileHandler('deleteinfected');
                    $antiVirus->handleFile($file->getLocalPath());
                    $this->invalidate($inputName, 'A virus was found in the file you uploaded.');
                }
                else
                {
                    foreach ($messages as $type => $typeMessages)
                    {
                        foreach ($typeMessages as $message)
                        {
                            $this->invalidate($inputName, $message);
                        }
                    }
                }
            }
            else
            {
                $this->invalidate($inputName, 'There was no file uploaded.');
            }
        }
        else
        {
            $this->invalidate($inputName, 'There was no file uploaded.');
        }
    }
    
    public function moveToPermanentStorage ()
    {
        if ($newLocalFileName = $this->getLocalFilename())
        {
            if (rename($this->localName, $newLocalFileName))
            {
                $this->_assign('temporary', false);
                $this->_assign('localName', $newLocalFileName);
            }
        }
    }
    
    /**
     * If this attachment is an uploaded file, get the path to where the file
     * will be permanently stored (i.e., until the file is dereferenced).
     * 
     * Returns null if this is not an uploaded file attachment.
     * 
     * @param bool $createDirectories
     *    If true, and this is a file, will attempt to create any missing
     *    subdirectories for storing the attachment file.
     * @return string
     */
    public function getLocalFilename ($temporary = false)
    {
        if ($this->hash)
        {
            $dir = ($temporary ? 'temp-files' : 'files');
            $dir = Bss_Core_PathUtils::path($this->getApplication()->getConfiguredPath('app', 'var'), $dir, $this->hash[0], $this->hash[1]);
        
            if (!is_dir($dir))
            {
                mkdir($dir, 0700, true);
            }
        
            return Bss_Core_PathUtils::path($dir, substr($this->hash, 2));
        }
        
        return null;
    }

    /**
     * Initialize the attachment from an uploaded file
     * 
     * @param Bss_Core_FileUpload $uploadFile
     */
    public function initFromFile ($uploadFile)
    {
        $this->hashDigest = $uploadFile->getHash();
        $this->filename = $uploadFile->getRemoteName();
        $this->contentType = Bss_MIME_Dictionary::getMime($uploadFile->getRemoteName(), $uploadFile->getContentType());
        $this->contentLength = $uploadFile->getContentLength();
        
        // TODO: Should actually move to holding, since we might not use this.
        
        $uploadFile->move($this->getLocalFilename(true));
    }
    
    /**
     * Send the file data using the given response object.
     * 
     * This method calls the notModified() and sendFile() methods of the given
     * response object, both of which may exit the request. See the 
     * documentation for those methods.
     * 
     * @param Bss_Core_IResponse $response
     * @param string $style
     */
    public function sendFile (Bss_Core_IResponse $response)
    {
        if ($hash = $this->hash)
        {
            $mtime = $this->uploadedDate->getTimestamp();
        
            if (!$response->notModified($hash, $mtime))
            {
                $response->sendFile(
                    $this->localName, $this->contentType, null, $this->contentLength,
                    array(
                        'etag' => $hash,
                        'lastModified' => $mtime,
                        'expires' => 0,
                        'attachmentName' => $this->remoteName,
                    )
                );
            }
        }
    }
    
    public function getDownloadLink ($base = '', $module = null, $item = null)
    {
        $link = '';

        if ($this->title)
        {
            $link = '<a href="' . $this->getDownloadUrl($base, $module, $item) . '" title='. $this->remoteName .'>' . $this->title . '</a>';
        }
        else
        {
            $link = '<a href="' . $this->getDownloadUrl($base, $module, $item) . '" title='. $this->remoteName .'>' . $this->remoteName . '</a>';
        }
        
        return $link;
    }
    
    public function getDownloadUrl ($base = '', $module = null, $item = null)
    {
        if ($base)
        {
            $base = rtrim($base, '/') . '/';
        }
        
        if ($module && $item)
        {
            return $base . "modules/{$module->id}/{$item->id}/download/{$this->id}";
        }
        
        return $base . 'files/' . $this->id . '/download';
    }

    public function beforeDelete ()
    {
        $dir = ($this->temporary ? 'temp-files' : 'files');
        $dir = Bss_Core_PathUtils::path($this->getApplication()->getConfiguredPath('app', 'var'), $dir, $this->hash[0], $this->hash[1]);
        $realDir = @dirname($this->localName);
    } 
}