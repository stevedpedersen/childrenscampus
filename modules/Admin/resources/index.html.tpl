<h1>Admin Settings</h1>

<div class="admin-settings">

<div class="list-group col-xs-6"> 
<div class="info-box">  
    <h3 class="info-box-header"><img src="assets/images/icon_courses.png" class="transparent control_icon" width="64" height="64"> Courses</h3>
    <a class="list-group-item" href="admin/courses">Manage Courses</a>
    <a class="list-group-item" href="admin/courses/queue">Manage Course Requests {if $crs}<span class="badge">{$crs|@count}</span>{/if}</a>
    <a class="list-group-item" href="admin/courses/types">Manage Course Types</a>
    </div>
</div>
<div class="list-group col-xs-6">
    <div class="info-box">
    <h3 class="info-box-header"><img src="assets/images/icon_course.png" class="transparent control_icon" width="64" height="64"> Observations</h3>
    <a class="list-group-item" href="admin/observations/current">Current Observations</a> 
    <a class="list-group-item" href="admin/observations/reservations">Upcoming Reservations</a> 
    <a class="list-group-item" href="admin/observations/missed">Missed Reservations</a> 
    </div>
</div>


<div class="list-group col-xs-6">
<div class="info-box">
    <h3 class="info-box-header"><img src="assets/images/icon_rooms.png" class="transparent control_icon" width="64" height="64"> Rooms</h3>
        <a class="list-group-item" href="admin/rooms">Manage Rooms</a> 
        <a class="list-group-item" href="admin/rooms/new">Create a Room</a> 
</div>
</div>
<div class="list-group col-xs-6">
    <div class="info-box">
    <h3 class="info-box-header"><img src="assets/images/icon_semester.png" class="transparent control_icon" width="64" height="64"> Dates</h3>
    <a class="list-group-item" href="admin/semester/configure">Manage Semesters</a>
    <a class="list-group-item" href="admin/settings/blockdates">Block Off Specific Dates</a>
    </div>
</div>


<div class="list-group col-xs-6 col-xs-offset-3">
    <div class="info-box">
    <h3 class="info-box-header"><img src="assets/images/icon_personal.png" class="transparent control_icon" width="64" height="64"> Accounts</h3>
    <a class="list-group-item" href="admin/accounts">Accounts</a> 
    <a class="list-group-item" href="admin/accounts/new">Create New Account</a>
    </div>
</div>

<div class="list-group col-xs-6">
    <div class="info-box">
    <h3 class="info-box-header"><img src="assets/images/icon_files.png" class="transparent control_icon" width="64" height="64"> Site Text & Email</h3>
    <a class="list-group-item" href="admin/welcome">Home Page & Contact Info Sidebar</a>
    <a class="list-group-item" href="admin/settings/email">Email Templates & Settings</a>    
    <a class="list-group-item" href="admin/courses/tasks">Course Tasks</a>
    <a class="list-group-item" href="admin/courses/instructions">Course Request Instructions</a>
    </div>
</div>


<div class="list-group col-xs-6">
    <div class="info-box">
    <h3 class="info-box-header"><img src="assets/images/icon_system.png" class="transparent control_icon" width="64" height="64"> Advanced</h3>
    <a class="list-group-item" href="admin/kiosk">Manage Kiosk Mode</a>
    <a class="list-group-item" href="admin/settings/siteNotice">Site Notice</a>
    <a class="list-group-item" href="admin/classdata">Class Data</a>
    <a class="list-group-item" href="admin/colophon">Colophon</a>
    <a class="hidden list-group-item" href="admin/search">Search Index Info</a>
    <a class="list-group-item" href="admin/roles">Roles and Access Levels</a>   
    <a class="hidden list-group-item" href="admin/cron">Cron Jobs</a>
    </div>
</div>

</div>