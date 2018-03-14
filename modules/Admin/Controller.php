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
        $adminPage = false;
        $path = $this->request->getFullRequestedUri();
        if ($this->hasPermission('admin') && (strpos($path, 'admin') !== false))
        {
            $adminPage = true;
        }
        $this->template->adminPage = $adminPage; 
    }
    
    /**
     * Dashboard.
     */
    public function index ()
    {
        $this->setPageTitle('Administrate');
        $this->template->crs = $this->schema('Ccheckin_Courses_Request')->getAll(array('orderBy' => 'requestDate'));
    }

    public function emailSettings ()
    {
        $siteSettings = $this->getApplication()->siteSettings;

        if ($this->request->wasPostedByUser())
        {
            switch ($this->getPostCommand()) {
                case 'save':
                    $siteSettings->setProperty('email-default-address', $this->request->getPostParameter('defaultAddress'));
                    $siteSettings->setProperty('email-signature', $this->request->getPostParameter('signature'));
                    $siteSettings->setProperty('email-course-allowed-teacher', $this->request->getPostParameter('courseAllowedTeacher'));
                    $siteSettings->setProperty('email-course-allowed-students', $this->request->getPostParameter('courseAllowedStudents'));
                    $siteSettings->setProperty('email-course-denied', $this->request->getPostParameter('courseDenied'));
                    $siteSettings->setProperty('email-course-requested-admin', $this->request->getPostParameter('courseRequestedAdmin'));
                    $siteSettings->setProperty('email-course-requested-teacher', $this->request->getPostParameter('courseRequestedTeacher'));
                    $siteSettings->setProperty('email-reservation-details', $this->request->getPostParameter('reservationDetails'));
                    $siteSettings->setProperty('email-reservation-reminder-time', $this->request->getPostParameter('reservationReminderTime'));
                    $siteSettings->setProperty('email-reservation-reminder', $this->request->getPostParameter('reservationReminder'));
                    $siteSettings->setProperty('email-reservation-missed', $this->request->getPostParameter('reservationMissed'));

                    $this->flash("Children's Campus email settings and content have been saved.");
                    $this->response->redirect('admin/settings/email');
                    exit;
                case 'send':
                    $viewer = $this->getAccount();
                    $command = $this->request->getPostParameter('command');
                    $which = array_keys($command['send']);
                    $which = array_pop($which);

                    if ($which)
                    {
                        $emailData = array();
                        $emailData['user'] = $viewer;
                        $emailManager = new Ccheckin_Admin_EmailManager($this->getApplication(), $this);                   

                        switch ($which) 
                        {
                            case 'courseRequestedAdmin':
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
                                // $emailData['courseRequest']->id = 0;
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
                                $emailData['course']->closeDate = new DateTime('now + 1 month');
                                $emailManager->processEmail('send' . ucfirst($which), $emailData, true);

                                $this->template->sendSuccess = 'You should receive a test email momentarily for Course-Allowed-Teacher template.';
                                break;

                            case 'courseAllowedStudents':
                                $emailData['course'] = new stdClass();
                                $emailData['course']->fullName = 'TEST: Introduction to Childhood Development';
                                $emailData['course']->shortName = 'TEST-CAD-0101-01-Spring-2025';
                                $emailData['course']->openDate = new DateTime;
                                $emailData['course']->closeDate = new DateTime('now + 1 month');
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
                        }
                    }
            }
        }

        $this->template->defaultAddress = $siteSettings->getProperty('email-default-address');
        $this->template->signature = $siteSettings->getProperty('email-signature');
        $this->template->courseRequestedAdmin = $siteSettings->getProperty('email-course-requested-admin');
        $this->template->courseRequestedTeacher = $siteSettings->getProperty('email-course-requested-teacher');
        $this->template->courseAllowedTeacher = $siteSettings->getProperty('email-course-allowed-teacher');
        $this->template->courseAllowedStudents = $siteSettings->getProperty('email-course-allowed-students');
        $this->template->courseDenied = $siteSettings->getProperty('email-course-denied');       
        $this->template->reservationDetails = $siteSettings->getProperty('email-reservation-details');
        $this->template->reservationReminder = $siteSettings->getProperty('email-reservation-reminder');
        $this->template->reservationReminderTime = $siteSettings->getProperty('email-reservation-reminder-time');
        $this->template->reservationMissed = $siteSettings->getProperty('email-reservation-missed');
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
                        $storedDates[] = $newDate;
                        $blockDates[] = new DateTime($newDate);
                        $siteSettings->setProperty('blocked-dates', json_encode($storedDates));
                        $this->flash('Blocked off date created.');
                        break;
                }
            }
        }

        $this->template->blockDates = $blockDates;
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
}
