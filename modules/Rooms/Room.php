<?php

class Ccheckin_Rooms_Room extends Bss_ActiveRecord_BaseWithAuthorization // implements Bss_AuthZ_IObjectProxy
{
    static $types = array(
        'participate',
        'observe'
    );

    public static function SchemaInfo ()
    {
        return array(
            // '__class' => 'Ccheckin_Rooms_Room',
            '__type' => 'ccheckin_rooms',
            '__azidPrefix' => 'at:ccheckin:rooms/Room/',
            '__pk' => array('id'),
            
            'id' => 'int',
            'name' => 'string',
            'description' => 'string',
            'observationType' => array('string', 'nativeName' => 'observation_type'),
            'maxObservers' => array('string', 'nativeName' => 'max_observers'),
            'hours' => 'string',
            'days' => 'string',
            
            // should orderBy be 'startTime'? startTime comes from RoomReservation schema.                       // was 'date' not 'start_time'
            'roomReservations' => array('1:N', 'to' => 'Ccheckin_Rooms_Reservation', 'reverseOf' => 'room', 'orderBy' => array('start_time')), 
        );
    }

    public function getTypes ()
    {
        return array(
            'participate',
            'observe'
        );
    }

    public function getDays ()
    {
        $days = $this->getProperty('days');
        
        return $days ? $days : array();
    }
    
    public function getHours ()
    {
        $hours = $this->getProperty('hours');
        
        return $hours ? $hours : array();
    }
    
    public function getDisplayHours ()
    {
        $hours = array();
        
        foreach ($this->hours as $hour)
        {
            $hours[] = $hour < 13 ? $hour . ' am' : ($hour - 12) . ' pm';
        }
        
        return implode(', ', $hours);
    }
    
    public function getDisplayDays ()
    {
        $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $display = array();
        
        foreach ($this->days as $day)
        {
            $display[] = $days[$day];
        }
        
        return implode(', ', $display);
    }
    
    public function getShortDays ()
    {
        $days = array('Su', 'M', 'T', 'W', 'Th', 'F', 'S');
        $display = array();
        
        foreach ($this->days as $day)
        {
            $display[] = $days[$day];
        }
        
        return implode('', $display);
    }
    
    public function validate ()
    {
        $errors = array();
        
        if (!$this->name)
        {
            $errors['name'] = 'Please provide a name for the room.';
        }
        
        if (!$this->maxObservers || !is_numeric($this->maxObservers))
        {
            $errors['maxObservers'] = 'Please enter a number for the maximum number of observers.';
        }
        
        return $errors;
    }

}
