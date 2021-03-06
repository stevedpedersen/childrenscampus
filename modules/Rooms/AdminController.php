<?php

/**
 *  Manage rooms, observations, and reservations as admin/programadmin
 *
 * @author  Charles O'Sullivan (chsoney@sfsu.edu)
 * @author  Steve Pedersen (pedersen@sfsu.edu)
 * @copyright   Copyright &copy; San Francisco State University.
 */
class Ccheckin_Rooms_AdminController extends Ccheckin_Master_Controller // At_Admin_Controller
{
    public static function getRouteMap ()
    {
        return array(
            'admin/rooms' => array('callback' => 'rooms'),
            'admin/rooms/:id' => array('callback' => 'editRoom', ':id' => '([0-9]+|new)'),
            'admin/observations/:userid/:id/' => array('callback' => 'editObservation', ':userid' => '[0-9]+', ':id' => '[0-9]+|all'),
            'admin/observations/current' => array('callback' => 'currentObservations'),
            'admin/observations/reservations' => array('callback' => 'currentReservations'),
            'admin/observations/missed' => array('callback' => 'missedObservations'),
            'admin/observations/generate' => array('callback' => 'generateObservations'),
        );
    }

    protected function beforeCallback ($callback)
    {
        parent::beforeCallback($callback);
        $this->template->clearBreadcrumbs();
        $this->addBreadcrumb('home', 'Home');
        $this->addBreadcrumb('admin', 'Admin');
        if (!$this->hasPermission('admin'))
        {
            $this->requirePermission('programadmin');
        }
        // if admin and on admin page, don't display 'Contact' sidebar
        $this->template->programAdmin = $this->hasPermission('programadmin');
        $this->template->adminPage = $this->hasPermission('programadmin') && (strpos($this->request->getFullRequestedUri(), 'admin') !== false); 
    }

    public function rooms () 
    {
        $this->setPageTitle('Manage Rooms');
        $roomSchema = $this->schema('Ccheckin_Rooms_Room');
        $message = ''; 
        
        if ($this->request->wasPostedByUser())
        {
            if ($command = $this->getPostCommand())
            {   
                switch ($command)
                {
                    case 'remove':
                        $rooms = $this->request->getPostParameter('rooms');
                        
                        if (!empty($rooms))
                        {
                            foreach ($rooms as $roomId)
                            {                               
                                if ($room = $roomSchema->get($roomId))
                                {
                                    $room->deleted = true;
                                    $room->save();
                                }
                            }
                            
                            $message = 'The selected rooms have been deleted.';
                        }
                        break;
                }
            }
        }

        $this->template->rooms = $roomSchema->find(
            $roomSchema->deleted->isNull()->orIf($roomSchema->deleted->isFalse()), array('orderBy' => 'name'));
        $this->template->message = $message;
    }

    public function editRoom () 
    {
        $this->addBreadcrumb('admin/rooms', 'Manage rooms');
        $id = $this->getRouteVariable('id');
        if (!preg_match('/^([0-9]+|new)$/', $id))
        {
            $this->notFound(); exit;
        }
        
        $rooms = $this->schema('Ccheckin_Rooms_Room');
        $errors = array();
        $new = false;
        
        $hours = array(7,8,9,10,11,12,13,14,15,16,17,18,19,20);
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday');
        
        if (is_numeric($id))
        {
            $room = $this->requireExists($rooms->findOne($rooms->id->equals($id)->andIf(
                $rooms->deleted->isNull()->orIf($rooms->deleted->isFalse()))));
            $this->setPageTitle('Edit Room: ' . $room->name);
            $this->template->schedule = $room->schedule;
        }
        else
        {
            $new = true;
            $room = $rooms->createInstance();
            $this->setPageTitle('Create Room');
        }

        if ($this->request->wasPostedByUser())
        {
            if ($command = $this->getPostCommand())
            {   
                switch ($command)
                {
                    case 'save':
                        $roomData = $this->request->getPostParameter('room');
                        
                        $room->observationType = $roomData['observationType'];
                        $room->name = $roomData['name'];
                        $room->maxObservers = $roomData['maxObservers'];
                        $room->description = $roomData['description'];
                        $room->schedule = json_encode($roomData['schedule']);

                        $errors = $room->validate();
                        
                        if (empty($errors))
                        {
                            $room->save();
                            $this->response->redirect('admin/rooms');
                        }
                        
                        break;
                }
            }
        }
        
        $this->template->hours = $hours;
        $this->template->days = $days;
        $this->template->room = $room;
        $this->template->new = $new;
        $this->template->observationTypes = $room->getTypes();
        $this->template->errors = $errors;
    }


    public function editObservation () 
    {
        $this->requirePermission('programadmin');
        $observations = $this->schema('Ccheckin_Rooms_Observation');
        $reservations = $this->schema('Ccheckin_Rooms_Reservation');
        $userid = $this->getRouteVariable('userid');
        $user = $this->requireExists($this->schema('Bss_AuthN_Account')->get($userid));
        $id = $this->getRouteVariable('id');

        if (is_numeric($id))
        {
            $observation = $this->requireExists($observations->get($id));
            $this->template->observation = $observation;
        }
       
        if ($this->request->wasPostedByUser())
        {
            if ($this->getPostCommand() === 'save')
            {   
                $duration = $this->request->getPostParameter('duration');

                if ($observation)
                {
                    if ($res = $reservations->findOne($reservations->observationId->equals($observation->id)))
                    {
                        $res->delete();
                    }
                    if (!$observation->endTime)
                    {
                        $observation->endTime = (clone $observation->startTime)->modify('+'.$duration.'minutes');
                    }
                    $observation->duration = $duration;
                    $observation->save();
                }

                $this->flash('Duration time has been updated for this user observation.');
                $this->response->redirect('admin/observations/'. $user->id . '/all');
            }
        }
        
        $userObservations = $observations->find(
            $observations->accountId->equals($user->id)->andIf(
                $observations->startTime->isNotNull()), 
            array('orderBy' => '-startTime')
        );

        $this->template->user = $user;
        $this->template->userObservations = $userObservations;
    }

    public function currentObservations () 
    {
        $reservations = $this->schema('Ccheckin_Rooms_Reservation');
        $observations = $reservations->find($reservations->checkedIn->isTrue(), array('orderBy' => '-startTime'));
        $checkout = $this->request->getQueryParameter('checkout', null);

        if ($checkout)
        {
            $res = $reservations->get($checkout);
            
            if ($res)
            {
                $now = new DateTime;
                $st = $res->observation->startTime;
                
                // if now is different year, month, or day than observation start time, set now to when the observation was supposed to end
                // this is for the math to check out when someone is editing an observation on a different day than it occurred.
                if ($now->format('Y')!==$st->format('Y') || $now->format('m')!==$st->format('m') || $now->format('d')!==$st->format('d'))
                {
                    $now = $res->endTime;
                }
                // set observation end time from now var
                $et = $now;
                // set start time to end time minus expected duration
                if ($st > $et)
                {
                    $tempDuration = $res->endTime->format('G') - $res->startTime->format('G');
                    $st = (clone $et)->modify('-' .$tempDuration. ' hours');
                }
                // calculate the exact or estimated duration
                $duration = abs(($et->format('G') - $st->format('G'))*60 + ($et->format('i') - $st->format('i')));

                $res->observation->endTime = $et;
                $res->observation->duration = $duration;
                $res->observation->save();
                $obs = $res->observation;
                $res->delete();

                $editUrl = '<a href="'. $this->baseUrl('admin/observations/' . $obs->accountId . '/' . $obs->id) .'">Edit Observation</a>';
                $this->flash('User was manually checked out of an observation lasting ' . $duration .
                    ' minutes. Should you need to edit the duration of this observation, you can find it here: ' . $editUrl
                );

                $observations = $reservations->find($reservations->checkedIn->isTrue(), array('orderBy' => '+startTime'));
            }
        }

        $this->template->reservations = $observations;
    }

    public function currentReservations () 
    {       
        $resSchema = $this->schema('Ccheckin_Rooms_Reservation');
        $view = $this->request->getQueryParameter('view', 'all');
        $view = 'upcoming'; // decided this function should only show upcoming, but kept query functionality in case.

        if ($view === 'upcoming')
        {
            $checkinRange = new DateTime('-30 minutes');
            $reservations = $resSchema->find(
                $resSchema->startTime->afterOrEquals($checkinRange)->andIf(
                $resSchema->checkedIn->isFalse()), 
                array('orderBy' => '+startTime')
            );
        }
        else
        {
            $reservations = $resSchema->getAll(array('orderBy' => '+startTime'));
        }
        
        $this->template->view = $view;
        $this->template->reservations = $reservations;
    }

    // Missed if 30 minutes late and still haven't checked in.
    public function missedObservations () 
    {
        $reservations = $this->schema('Ccheckin_Rooms_Reservation');
        $condition = $reservations->allTrue(
            $reservations->startTime->before(new DateTime('now - 30 minutes')),
            $reservations->checkedIn->isFalse()
        );
        $missed = $reservations->find($condition, array('orderBy' => '-startTime'));

        $this->template->reservations = $missed;
    }

}