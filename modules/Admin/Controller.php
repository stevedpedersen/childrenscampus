<?php

/**
 */
class Ccheckin_Admin_Controller extends Ccheckin_Master_Controller
{
    public static function getRouteMap ()
    {
        return array(        
            '/admin' => array('callback' => 'index'),
            '/admin/colophon' => array('callback' => 'colophon'),
			'/admin/apc' => array('callback' => 'clearMemoryCache'),
            '/admin/cron' => array('callback' => 'cron'),
            '/admin/settings/siteNotice' => array('callback' => 'siteNotice'),
            '/admin/settings/blockDates' => array('callback' => 'blockDates'),
            '/admin/settings/email' => array('callback' => 'emailSettings'),
            '/admin/kiosk' => array('callback' => 'kioskMode'),
            '/admin/reports/generate' => array('callback' => 'reports'),
            '/admin/migrate' => array('callback' => 'migrate'),
            '/admin/files/:fid/download' => array( 'callback' => 'download', 'fid' => '[0-9]+'),
        );
    }
    
    protected function beforeCallback ($callback)
    {
        parent::beforeCallback($callback);
        $this->requirePermission('admin');
        $this->template->clearBreadcrumbs();
        $this->addBreadcrumb('home', 'Home');
        $this->addBreadcrumb('admin', 'Admin');
        // if admin and on admin page, don't display 'Contact' sidebar
        $this->template->adminPage = $this->hasPermission('admin') && (strpos($this->request->getFullRequestedUri(), 'admin') !== false);
    }

    public function download ()
    {
        $account = $this->requireLogin();
        
        $fid = $this->getRouteVariable('fid');
        $file = $this->requireExists($this->schema('Ccheckin_Admin_File')->get($fid));
        
        if ($file->uploadedBy && ($account->id != $file->uploadedBy->id))
        {
            
            if ($item = $this->getRouteVariable('item'))
            {
                $authZ = $this->getAuthorizationManager();
                $extension = $item->extension;
                
                if ($authZ->hasPermission($account, $extension->getItemViewTask(), $item))
                {
                    $file->sendFile($this->response);
                }
            }
            
            // $this->requirePermission('file download');
        }
        
        $file->sendFile($this->response);
    }

    public function reports ()
    {
        set_time_limit(0);
        $viewer = $this->requireLogin();
        $this->requirePermission('reports generate');
        $migrationDate = new DateTime('2018-05-01');

        $courseSchema = $this->schema('Ccheckin_Courses_Course');
        $obsSchema = $this->schema('Ccheckin_Rooms_Observation');
        $resSchema = $this->schema('Ccheckin_Rooms_Reservation');
        $roomSchema = $this->schema('Ccheckin_Rooms_Room');
        $semSchema = $this->schema('Ccheckin_Semesters_Semester');
        $userSchema = $this->schema('Bss_AuthN_Account');
        $roleSchema = $this->schema('Ccheckin_AuthN_Role');

        $tomorrow = new DateTime('+1 day');
        $filename = 'CC-Observation-Report-' . date('Y-m-d') . '.csv';
        $obsData = array();
        $orgs = array();

        if ($this->request->wasPostedByUser())
        {
            $from = $this->request->getPostParameter('from', 0);
            $until = $this->request->getPostParameter('until', $tomorrow);

            try {
                $test = new DateTime($from);
                $test = new DateTime($until);
            } catch (Exception $e) {
                $this->flash('Invalid Date/Time format. Please try again.');
                $this->response->redirect('admin/reports/generate');
                exit;
            }

            $observations = $obsSchema->find(
                $obsSchema->startTime->afterOrEquals($from)->andIf(
                $obsSchema->startTime->beforeOrEquals($until)),
                array('orderBy' => 'startTime')
            );

            // NOTE: college & department fields will only be fetched post-migration
            foreach ($observations as $obs)
            {
                if ($obs->duration)
                {
                    $course = $obs->purpose->object->course;
                    if (!in_array($course->shortName, array_keys($orgs)))
                    {   // cache API results
                        $orgs[$course->shortName] = array();
                        $orgs[$course->shortName]['college'] = ($obs->startTime > $migrationDate) ? $course->college : '';
                        $orgs[$course->shortName]['department'] = ($obs->startTime > $migrationDate) ? $course->department : '';
                    }
              
                    // create a dummy semester in case it gets deleted from the system
                    if (!($semester = $semSchema->findOne($semSchema->startDate->equals($course->startDate)))) {
                        $semesterDate = (clone $course->startDate)->modify('+2 weeks');
                        $semesterCode = Ccheckin_Semesters_Semester::guessActiveSemester(true, $semesterDate, $course->endDate);
                        $semester = new stdClass;
                        $semester->display = Ccheckin_Semesters_Semester::ConvertToDescription($semesterCode);
                    }

                    $obsData[$obs->id] = array();
                    $obsData[$obs->id]['obsId'] = $obs->id;
                    $obsData[$obs->id]['course'] = $course->shortName;
                    $obsData[$obs->id]['semester'] = $semester->display;
                    $obsData[$obs->id]['college'] = $orgs[$course->shortName]['college'];
                    $obsData[$obs->id]['department'] = $orgs[$course->shortName]['department'];
                    $obsData[$obs->id]['firstName'] = $obs->account->firstName;
                    $obsData[$obs->id]['lastName'] = $obs->account->lastName;
                    $obsData[$obs->id]['username'] = $obs->account->username;
                    $obsData[$obs->id]['email'] = $obs->account->emailAddress;
                    $obsData[$obs->id]['duration'] = $obs->duration ?? 0;                
                }
            }

            header("Content-Type: application/download\n");
            header('Content-Disposition: attachment; filename="' .$filename. '"' . "\n");
            $handle = fopen('php://output', 'w+');

            if ($handle)
            {
                $headers = array(
                    'Semester',
                    'Department',
                    'Course Short Name',
                    'First Name',
                    'Last Name',
                    'Student ID',
                    'Email',
                    'Duration (minutes)'
                );
                fputcsv($handle, $headers);

                foreach ($obsData as $obs)
                {
                    $row = array(
                        $obs['semester'],
                        $obs['department'],
                        $obs['course'],
                        $obs['firstName'],
                        $obs['lastName'],
                        $obs['username'],
                        $obs['email'],
                        $obs['duration'],
                    );
                    fputcsv($handle, $row);
                }
            }
            
            exit;
        }

        $this->template->tomorrow = $tomorrow;
    }
  
    /**
     * Dashboard.
     */
    public function index ()
    {
        $this->setPageTitle('Administrate');
        $requestSchema = $this->schema('Ccheckin_Courses_Request');
        $courseSchema = $this->schema('Ccheckin_Courses_Course');
        $deletedCourses = $courseSchema->find($courseSchema->deleted->isTrue());
        $dcs = array();
        
        foreach ($deletedCourses as $dc)
        {
            $dcs[] = $dc->id;
        }
        $crs = $requestSchema->find(
            $requestSchema->courseId->notInList($dcs),
            array('orderBy' => 'requestDate')
        );

        $this->template->crs = $crs;
    }

    public function kioskMode ()
    {
        $cookieName = 'cc-kiosk';
        $cookieValue = 'kiosk';
        $isKiosk = false;
        
        if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] == $cookieValue)
        {
            $isKiosk = true;
        }
        
        if ($command = $this->request->getPostParameter('command'))
        {
            $temp = array_keys($command);
            $action = array_shift($temp);

            switch ($action)
            {
                case 'set':
                    if (!$isKiosk) setCookie($cookieName, $cookieValue, time()+60*60*24*30*12, '/');
                    $this->response->redirect('admin/kiosk?message=set');
                    break;
                case 'unset':
                    if ($isKiosk) setCookie($cookieName, false, time()+60*60*24*30*12, '/');
                    $this->response->redirect('admin/kiosk?message=unset');
                    break;
            }
        }
        
        $this->setPageTitle('Manage Kiosk Mode');
        $this->template->message = $this->request->getQueryParameter('message');
        $this->template->isKiosk = $isKiosk;
    }

    public function updateEmailAttachments ($attachmentData)
    {
        $files = $this->schema('Ccheckin_Admin_File');
        $attachedFiles = array();

        foreach ($attachmentData as $emailKey => $fileIds)
        {
            foreach ($fileIds as $fileId)
            {
                if (!isset($attachedFiles[$fileId]))
                {
                    $attachedFiles[$fileId] = array();
                }
                if (!in_array($emailKey, $attachedFiles[$fileId]))
                {
                    $attachedFiles[$fileId][] = $emailKey;
                }
            }
        }

        // make sure each file matches the state of posted data
        foreach ($files->getAll() as $file)
        {
            if (!in_array($file->id, array_keys($attachedFiles)))
            {
                $file->attachedEmailKeys = array();
            }
            else // make sure all the files match the posted data
            {
                $file->attachedEmailKeys = $attachedFiles[$file->id];
            }
            $file->save();
        }
    }

    public function emailSettings ()
    {
        $siteSettings = $this->getApplication()->siteSettings;
        $files = $this->schema('Ccheckin_Admin_File');
        $removedFiles = array();
        $reminderOptions = array('1 day', '2 days', '12 hours', '6 hours', '2 hours', '1 hour');

        if ($this->request->wasPostedByUser())
        {
            if ($removedFiles = $this->request->getPostParameter('removed-files', array()))
            {
                $removedFiles = $files->find($files->id->inList($removedFiles), array('arrayKey' => 'id'));
            }

            if ($attachments = $this->request->getPostParameter('attachments'))
            {
                $attRecords = $files->find($files->id->inList($attachments));
                
                foreach ($attRecords as $record)
                {
                    if (empty($removedFiles[$record->id]))
                    {
                        $attachments[$record->id] = $record;
                    }
                }
            }

            switch ($this->getPostCommand()) {
                case 'upload':
                    $file = $files->createInstance();
                    $file->createFromRequest($this->request, 'attachment');
                    
                    if ($file->isValid())
                    {
                        $file->uploadedBy = $this->getAccount();
                        $file->save();

                        $this->flash('The file has been uploaded to the server.');
                        $this->response->redirect('admin/settings/email');
                    }
                    
                    $this->template->errors = $file->getValidationMessages();
                    break;

                case 'remove-attachment':
                    $command = $this->request->getPostParameter('command');
                    $tmpArray = array_keys($command['remove-attachment']);
                    $id = array_shift($tmpArray);
                    if ($fileToRemove = $files->get($id))
                    {
                        $removedFiles[$fileToRemove->id] = $fileToRemove;
                        $fileToRemove->delete();
                    }

                    $this->flash("This file has been removed from the server.");
                    break;

                case 'save':
                    $testing = $this->request->getPostParameter('testingOnly');
                    $testingOnly = ((is_null($testing) || $testing === 0) ? 0 : 1);
                    $siteSettings->setProperty('email-testing-only', $testingOnly);
                    $siteSettings->setProperty('email-test-address', $this->request->getPostParameter('testAddress'));
                    $siteSettings->setProperty('email-default-address', $this->request->getPostParameter('defaultAddress'));
                    $siteSettings->setProperty('email-signature', $this->request->getPostParameter('signature'));
                    $siteSettings->setProperty('email-new-account', $this->request->getPostParameter('newAccount'));
                    $siteSettings->setProperty('email-course-allowed-teacher', $this->request->getPostParameter('courseAllowedTeacher'));
                    $siteSettings->setProperty('email-course-allowed-students', $this->request->getPostParameter('courseAllowedStudents'));
                    $siteSettings->setProperty('email-course-denied', $this->request->getPostParameter('courseDenied'));
                    $siteSettings->setProperty('email-course-requested-admin', $this->request->getPostParameter('courseRequestedAdmin'));
                    $siteSettings->setProperty('email-course-requested-teacher', $this->request->getPostParameter('courseRequestedTeacher'));
                    $siteSettings->setProperty('email-reservation-details', $this->request->getPostParameter('reservationDetails'));
                    $siteSettings->setProperty('email-reservation-reminder-time', $this->request->getPostParameter('reservationReminderTime'));
                    $siteSettings->setProperty('email-reservation-reminder', $this->request->getPostParameter('reservationReminder'));
                    $siteSettings->setProperty('email-reservation-missed', $this->request->getPostParameter('reservationMissed'));
                    $siteSettings->setProperty('email-reservation-canceled', $this->request->getPostParameter('reservationCanceled'));

                    $attachmentData = $this->request->getPostParameter('attachment');
                    $this->updateEmailAttachments($attachmentData);

                    $this->flash("Children's Campus email settings and content have been saved.");
                    $this->response->redirect('admin/settings/email');
                    exit;
                    
                case 'sendtest':
                    $viewer = $this->getAccount();
                    $command = $this->request->getPostParameter('command');
                    $which = array_keys($command['sendtest']);
                    $which = array_pop($which);

                    if ($which)
                    {
                        $emailData = array();
                        $emailData['user'] = $viewer;
                        $emailManager = new Ccheckin_Admin_EmailManager($this->getApplication(), $this);                   

                        switch ($which) 
                        {
                            case 'newAccount':
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);
                                
                                $this->template->sendSuccess = 'You should receive a test email momentarily for New-Account template.';
                                break;

                            case 'courseRequestedAdmin':
                                $emailData['requestingUser'] = $viewer;
                                $emailData['courseRequest'] = new stdClass();
                                $emailData['courseRequest']->id = 0;
                                $emailData['courseRequest']->fullName = 'TEST: Introduction to Childhood Development';
                                $emailData['courseRequest']->shortName = 'TEST-CAD-0101-01-Spring-2025';
                                $emailData['courseRequest']->semester = 'TEST Spring 2025';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);
                                
                                $this->template->sendSuccess = 'You should receive a test email momentarily for Course-Requested-Admin template.';
                                break;

                            case 'courseRequestedTeacher':
                                $emailData['courseRequest'] = new stdClass();
                                $emailData['courseRequest']->fullName = 'TEST: Introduction to Childhood Development';
                                $emailData['courseRequest']->shortName = 'TEST-CAD-0101-01-Spring-2025';
                                $emailData['courseRequest']->semester = 'TEST Spring 2025';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Course-Requested-Teacher template.';                                
                                break;

                            case 'courseAllowedTeacher':
                                $emailData['course'] = new stdClass();
                                $emailData['course']->id = 0;
                                $emailData['course']->fullName = 'TEST: Introduction to Childhood Development';
                                $emailData['course']->shortName = 'TEST-CAD-0101-01-Spring-2025';
                                $emailData['course']->openDate = new DateTime;
                                $emailData['course']->lastDate = new DateTime('now + 1 month');
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Course-Allowed-Teacher template.';
                                break;

                            case 'courseAllowedStudents':
                                $emailData['course'] = new stdClass();
                                $emailData['course']->fullName = 'TEST: Introduction to Childhood Development';
                                $emailData['course']->shortName = 'TEST-CAD-0101-01-Spring-2025';
                                $emailData['course']->openDate = new DateTime;
                                $emailData['course']->lastDate = new DateTime('now + 1 month');
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Course-Allowed-Students template.';
                                break;

                            case 'courseDenied':
                                $emailData['course'] = new stdClass();
                                $emailData['course']->fullName = 'TEST: Introduction to Childhood Development';
                                $emailData['course']->shortName = 'TEST-CAD-0101-01-Spring-2025';
                                $emailData['course']->semester = 'TEST Spring 2025';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Course-Denied template.';                       
                                break;

                            case 'reservationDetails':
                                $emailData['reservation'] = new stdClass();
                                $emailData['reservation']->id = 0;
                                $emailData['reservation']->startTime = new DateTime;
                                $emailData['reservation']->purpose = 'TEST Observation only course - TEST-CAD-0101-01-Spring-2025';
                                $emailData['reservation']->room = 'TEST CC-221';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Reservation-Details template.';  
                                break;
                            
                            case 'reservationReminder':
                                $emailData['reservation'] = new stdClass();
                                $emailData['reservation']->id = 0;
                                $emailData['reservation']->startTime = new DateTime;
                                $emailData['reservation']->purpose = 'TEST Observation only course - TEST-CAD-0101-01-Spring-2025';
                                $emailData['reservation']->room = 'TEST CC-221';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Reservation-Reminder template.';  
                                break;

                            case 'reservationMissed':
                                $emailData['reservation'] = new stdClass();
                                $emailData['reservation']->startTime = new DateTime;
                                $emailData['reservation']->purpose = 'TEST Observation only course - TEST-CAD-0101-01-Spring-2025';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Reservation-Reminder template.';  
                                break;

                            case 'reservationCanceled':
                                $emailData['reservation'] = new stdClass();
                                $emailData['reservation_date'] = new DateTime;
                                $emailData['reservation_purpose'] = 'TEST Observation only course - TEST-CAD-0101-01-Spring-2025';
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Reservation-Reminder template.';  
                                break;
                        }
                    }
            }
        }

        $accounts = $this->schema('Bss_AuthN_Account');
        $this->template->systemNotificationRecipients = $accounts->find($accounts->receiveAdminNotifications->isTrue());
        $this->template->authZ = $this->getApplication()->authorizationManager;
        $this->template->removedFiles = $removedFiles;
        $this->template->attachments = $files->getAll();
        $this->template->testingOnly = $siteSettings->getProperty('email-testing-only', 0);
        $this->template->testAddress = $siteSettings->getProperty('email-test-address');
        $this->template->defaultAddress = $siteSettings->getProperty('email-default-address');
        $this->template->signature = $siteSettings->getProperty('email-signature');
        $this->template->newAccount = $siteSettings->getProperty('email-new-account');
        $this->template->courseRequestedAdmin = $siteSettings->getProperty('email-course-requested-admin');
        $this->template->courseRequestedTeacher = $siteSettings->getProperty('email-course-requested-teacher');
        $this->template->courseAllowedTeacher = $siteSettings->getProperty('email-course-allowed-teacher');
        $this->template->courseAllowedStudents = $siteSettings->getProperty('email-course-allowed-students');
        $this->template->courseDenied = $siteSettings->getProperty('email-course-denied');       
        $this->template->reservationDetails = $siteSettings->getProperty('email-reservation-details');
        $this->template->reservationReminder = $siteSettings->getProperty('email-reservation-reminder');
        $this->template->reservationReminderTime = $siteSettings->getProperty('email-reservation-reminder-time');
        $this->template->reservationMissed = $siteSettings->getProperty('email-reservation-missed');
        $this->template->reservationCanceled = $siteSettings->getProperty('email-reservation-canceled');
        $this->template->reminderOptions = $reminderOptions;
    }
    
    /**
     */
    public function colophon ()
    {
        $moduleManager = $this->getApplication()->moduleManager;
        $this->template->moduleList = $moduleManager->getModules();
    }

    public function blockDates ()
    {      
        $siteSettings = $this->getApplication()->siteSettings;
        $storedDates = json_decode($siteSettings->getProperty('blocked-dates'), true);
        $blockDates = $this->convertToDateTimes($storedDates);

        if ($this->request->wasPostedByUser())
        {
            if ($command = $this->getPostCommand())
            {
                switch ($command)
                {
                    case 'remove':
                        if ($datesToRemove = $this->request->getPostParameter('blockDates'))
                        {
                            foreach ($datesToRemove as $i => $date)
                            {   
                                unset($storedDates[$i]);
                                $updatedBlockDates = array_values($storedDates);
                                $blockDates = $this->convertToDateTimes($updatedBlockDates);
                            }

                            $siteSettings->setProperty('blocked-dates', json_encode($updatedBlockDates));
                            $this->flash('The specified dates have been removed.');
                        }
                        break;

                    case 'add':
                        $newDate = $this->request->getPostParameter('blockeddatenew');
                        try {
                            $blockDates[] = new DateTime($newDate);
                            $storedDates[] = $newDate;
                            $this->sendReservationCanceledNotification($newDate);
                            $siteSettings->setProperty('blocked-dates', json_encode($storedDates));
                            $this->flash('Blocked off date added.');

                        } catch (Exception $e) {
                            $this->flash('Error: Invalid date format.');
                        }                     

                        break;
                }
            }
        }

        $this->template->blockDates = $blockDates;
    }

    public function sendReservationCanceledNotification ($blockedDate)
    {

        $reservations = $this->schema('Ccheckin_Rooms_Reservation');
        $blocked = new DateTime($blockedDate);
        $canceled = array();

        $cond = $reservations->allTrue(
            $reservations->startTime->after(new DateTime()),
            $reservations->missed->isNull()->orIf($reservations->missed->isFalse()),
            $reservations->checkedIn->isNull()->orIf($reservations->checkedIn->isFalse())

        );
        $upcoming = $reservations->find($cond);

        foreach ($upcoming as $reservation)
        {
            if ($blocked->format('Y/m/d') === $reservation->startTime->format('Y/m/d'))
            {
                $canceled[] = $reservation;
            }
        }

        $emailManager = new Ccheckin_Admin_EmailManager($this->getApplication(), $this);
        
        // notify students of cancellation
        foreach ($canceled as $reservation)
        {
            $emailData = array();        
            $emailData['reservation_date'] = $reservation->startTime;
            $emailData['reservation_purpose'] = $reservation->observation->purpose->shortDescription;
            $emailData['user'] = $reservation->account;
            $emailManager->processEmail('sendReservationCanceled', $emailData);

            $observation = $reservation->observation;
            $reservation->delete();
            $observation->delete();
        }
    }
 
    /**
     * Set the site notice.
     */
    public function siteNotice ()
    {
        $this->addBreadcrumb('admin', 'Administrate');
        $this->setPageTitle('Site notice');
        $settings = $this->getApplication()->siteSettings;
        
        if ($this->request->wasPostedByUser())
        {
            $sanitizer = new Bss_RichText_HtmlSanitizer;
            $settings->siteNotice = $sanitizer->sanitize($this->request->getPostParameter('siteNotice'));
            $this->response->redirect('admin');
        }
        
        $this->template->siteNotice = $settings->siteNotice;
    }

	/**
	 */
	public function clearMemoryCache ()
	{
		if (function_exists('apc_clear_cache'))
		{
			$this->template->cacheExists = true;
			
			if ($this->request->wasPostedByUser())
			{
                set_time_limit(0);
                $this->request->getSession()->release();
                
				$this->userMessage('Cleared op-code and user cache.');
				apc_clear_cache();
				apc_clear_cache('user');
                
                // Force the permission cache to rebuild.
                $this->getAuthorizationManager()->updateCache();
			}
		}
	}
    
    public function cron ()
    {
        $moduleManager = $this->application->moduleManager;
        $xp = $moduleManager->getExtensionPoint('bss:core:cron/jobs');
        $lastRunDates = $xp->getLastRunDates();
        $cronJobMap = array();
        
        if ($this->request->wasPostedByUser() && $this->getPostCommand() === 'invoke')
        {
            $data = $this->getPostCommandData();
            $now = new DateTime;
            
            foreach ($data as $name => $nonce)
            {
                if (($job = $xp->getExtensionByName($name)))
                {
                    $xp->runJob($name);
                    $lastRunDates[$name] = $now;
                }
            }
        }
        
        foreach ($xp->getExtensionDefinitions() as $jobName => $jobInfo)
        {
            $cronJobMap[$jobName] = array(
                'name' => $jobName,
                'instanceOf' => $jobInfo[0],
                'module' => $jobInfo[1],
                'lastRun' => (isset($lastRunDates[$jobName]) ? $lastRunDates[$jobName]->format('c') : 'never'),
            );
        }
        
        $this->template->cronJobs = $cronJobMap;
    }

    public function migrate ()
    {
        $app = $this->getApplication();
        $migrationComplete = $app->siteSettings->getProperty('migration-complete');

        if (!$migrationComplete)
        {
            set_time_limit(0);
            
            $this->requirePermission('admin');
            $semSchema = $this->schema('Ccheckin_Semesters_Semester');
            $courseSchema = $this->schema('Ccheckin_Courses_Course');
            $facetSchema = $this->schema('Ccheckin_Courses_Facet');
            $facetTypeSchema = $this->schema('Ccheckin_Courses_FacetType');
            $reservations = $this->schema('Ccheckin_Rooms_Reservation');
            $accounts = $this->schema('Bss_AuthN_Account');

            // Generate Semester 'internal'
            foreach ($semSchema->find($semSchema->internal->isNull()) as $semester)
            {
                $semester->internal = Ccheckin_Semesters_Semester::ConvertToCode($semester->display);
                $semester->save();
            }

            // Convert Facets 'tasks' from serialized to JSON
            $facets = $facetSchema->getAll();
            $allTasks = (isset($facets[0]) ? $facets[0]->GetAllTasks() : array());

            foreach ($facets as $facet)
            {
                $tasks = array();
                $serialTasks = $facet->getTasks(true);
                
                if ($arrTasks = @unserialize($serialTasks))
                {
                    foreach ($arrTasks as $task)
                    {
                        $key = array_search($task, $allTasks);
                        $tasks[$key] = $task;
                    }
                    $facet->tasks = $tasks;
                    $facet->save();
                }
            }

            // Generate Course_Enroll_Map 'term' from Semester->internal
            foreach ($courseSchema->getAll() as $course)
            {
                $semester = $semSchema->findOne($semSchema->startDate->equals($course->startDate));
                $semCode = (!$semester ? Ccheckin_Semesters_Semester::guessActiveSemester(true, $course->startDate) : $semester->internal);

                foreach ($course->enrollments as $enrollee)
                {
                    $course->enrollments->setProperty($enrollee, 'term', $semCode);
                }
                $course->enrollments->save();
            }

            // Update the sortName attribute of facet types
            foreach ($facetTypeSchema->getAll() as $type)
            {
                $type->name = $type->name;
                $type->save();
            }


            // Delete old missed reservations
            $condition = $reservations->anyTrue(
                $reservations->allTrue(
                    $reservations->startTime->before(new DateTime('-3 days')),
                    $reservations->checkedIn->isFalse()->orIf($reservations->checkedIn->isNull())
                ),
                $reservations->allTrue(
                    $reservations->startTime->before(new DateTime('-3 days')),
                    $reservations->missed->isTrue(),
                    $reservations->checkedIn->isFalse()->orIf($reservations->checkedIn->isNull())
                )                
            );

            $missed = $reservations->find($condition);
            foreach ($missed as $reservation)
            {
                $observation = $reservation->observation;
                $reservation->delete();
                if (isset($observation->duration) && !$observation->duration)
                {
                    $observation->delete();
                }      
            }

            // Forgive all missed reservation penalties
            $missedReservationAccounts = $accounts->find($accounts->missedReservation->isTrue());

            foreach ($missedReservationAccounts as $account)
            {
                $account->missedReservation = false;
                $account->save();
            }

            $now = new DateTime;
            $app->siteSettings->setProperty('missed-reservations-cleared-date', $now->format('Y-m-d'));   


            // Set migration as complete
            $app->siteSettings->setProperty('migration-complete', true);
        }

        
    }
}
