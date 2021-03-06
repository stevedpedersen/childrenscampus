<h1>Upcoming Reservations</h1>
{if $reservations}
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Time</th>
            <th>User</th>
            <th>Room</th>
            <th>Course</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
{foreach item='reservation' from=$reservations}
    <tr>
        <td>{$reservation->startTime->format('M j, Y')}<br><strong>{$reservation->startTime->format('ga')} to {$reservation->endTime->format('ga')}</strong></td>
        <td>{$reservation->account->firstName} {$reservation->account->lastName}</td>
        <td>{$reservation->room->name|escape}</td>
        <td>{$reservation->observation->purpose->object->course->shortNameAbbrev}</td>
        <td class="actions">
			<a href="reservations/delete/{$reservation->id}" class="btn btn-xs btn-default">cancel</a>
            <a href="reservations/view/{$reservation->id}" class="btn btn-xs btn-default">view</a>
		{if $pAdmin}<a href="reservations/override/{$reservation->id}" class="btn btn-xs btn-default">check-in</a>{/if}
		</td>
    </tr>
{/foreach}
    </tbody>
</table>
{else}
<div class="flash">
    <div class="warning message"><p>There are no reservations set up.</p></div>
</div>
{/if}