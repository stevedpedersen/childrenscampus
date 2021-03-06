<?php

class Ccheckin_Semesters_AdminController extends At_Admin_Controller
{

    public static function getRouteMap ()
    {
        return array(
            'admin/semester/configure' => array('callback' => 'configure'),
        );
    }

    protected function beforeCallback ($callback)
    {
        parent::beforeCallback($callback);
        $this->template->clearBreadcrumbs();
        $this->addBreadcrumb('home', 'Home');
        $this->addBreadcrumb('admin', 'Admin');
        // if admin and on admin page, don't display 'Contact' sidebar
        $this->template->adminPage = $this->hasPermission('admin') && (strpos($this->request->getFullRequestedUri(), 'admin') !== false); 
    }

    public function configure ()
    {
        $this->setPageTitle('Configure Semesters');
        $semesters = $this->schema('Ccheckin_Semesters_Semester');
        $errors = array();
        $message = '';

        if ($this->request->wasPostedByUser())
        {
            if ($command = $this->getPostCommand())
            {
                switch ($command)
                {
                    case 'remove':
                        if ($sems = $this->request->getPostParameter('semesters'))
                        {
                            foreach ($sems as $sem)
                            {                            
                                if ($semester = $semesters->get($sem))
                                {
                                    $semester->delete();
                                }
                            }

                            $message = 'The semesters have been deleted';
                        }
                        break;

                    case 'add':
                        $semester = $semesters->createInstance();
                        
                        if ($startDate = $this->request->getPostParameter('startDate'))
                        {
                            $semester->startDate = new DateTime($startDate);
                        }
                        if ($endDate = $this->request->getPostParameter('endDate'))
                        {
                            $semester->endDate = new DateTime($endDate);
                        }
                        if ($openDate = $this->request->getPostParameter('openDate'))
                        {
                            $semester->openDate = new DateTime($openDate);
                        }
                        else
                        {
                            $semester->openDate = new DateTime($startDate);
                        }
                        if ($lastDate = $this->request->getPostParameter('lastDate'))
                        {
                            $semester->lastDate = new DateTime($lastDate);
                        }
                        else
                        {
                            $semester->lastDate = new DateTime($endDate);
                        }
             
                        $term = $this->request->getPostParameter('term');
                        $semester->display = $term . ' ' . $semester->startDate->format('Y');
                        $codes = array('Spring'=>3, 'Summer'=>5, 'Fall'=>7, 'Winter'=>1);
                        $m = $semester->startDate->format('m');
                        $y = $semester->startDate->format('Y');
                        $y = $y[0] . substr($y, 2);
                        if ($term === 'Winter' && $m = '12') { $y++; }
                        $semester->internal = $y . $codes[$term];

                        $errors = $semester->validate();
                        
                        if (empty($errors))
                        {
                            $semester->save();
                            $message = 'Semester created';
                        }
                        break;
                }
            }
        }

        $view = $this->request->getQueryParameter('view', 'recent');
        if ($view !== 'all')
        {
            if ($this->hasPermission('admin'))
            {
                $semesters = $semesters->find($semesters->startDate->after(new DateTime('-3 years')), array('orderBy' => '-startDate'));
            }
        }
        else
        {
            $semesters = $semesters->getAll(array('orderBy' => '-startDate'));
        }
        
        
        $this->template->semesters = $semesters;
        $this->template->terms = Ccheckin_Semesters_Semester::GetTerms();
        $this->template->view = $view;
        $this->template->message = $message;
        $this->template->errors = $errors;
    }

}
