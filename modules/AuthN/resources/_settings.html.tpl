
{if $pAdmin}
	<fieldset class="field">
		<legend>Roles</legend>
		<ul class="list-group">
{foreach item="role" from=$roleList}
			<li>
				<label for="account-role-{$role->id}">
				<input type="checkbox" name="role[{$role->id}]" id="account-role-{$role->id}" class="account-role-{$role->name}" 
				{if $account->roles->has($role)}checked aria-checked="true"{else}aria-checked="false"{/if} />
				{$role->name|escape}</label>
			</li>
{/foreach}
		</ul>
	</fieldset>
{/if}
<fieldset class="field">
	<legend>Activate account</legend>
	<ul class="list-group">
		<li>
			<label for="account-status">
			<input type="checkbox" name="status" id="account-status" 
			{if $account->isActive}checked aria-checked="true"{else}aria-checked="false"{/if} />
			Active</label>
		</li>
	</ul>
</fieldset>
{if $notify && $newAccount}
<fieldset class="field">
	<legend>Notify user</legend>
	<label for="notify">
		<input type="checkbox" name="notify" id="notify" checked aria-checked="true" value=true />
		Notify user of account
	</label>
</fieldset>
<br>
{/if}
{if $canEditNotifications}
	{if ($authZ->hasPermission($account, 'admin') || $authZ->hasPermission($account, 'receive system notifications'))}
		{assign var=canReceiveNotifications value=true}
	{else}
		{assign var=canReceiveNotifications value=false}
	{/if}
<fieldset class="field">
	<legend>Admin email notifications</legend>
	
	<ul class="list-group">
		<li>
			<label for="receiveAdminNotifications">		 
			{if !$canReceiveNotifications}
				Unable to 
			{else}
				<input type="checkbox" name="receiveAdminNotifications" id="receiveAdminNotifications"
				{if ($account->receiveAdminNotifications) && !$newAccount}
					checked aria-checked="true" value=true
				{else}
					aria-checked="false" value=false
				{/if}
				/>
			{/if} 	
			
			Receive admin notifications</label>
			{if !$canReceiveNotifications}<p class="">Note: This user is unable to receive system notifications. Contact an admin if this is incorrect or upgrade this user's role to one that can receive system notifications. If this is a new Admin user, save it with Administrator role and then edit it again.</p>{/if}
		</li>
		{if $canReceiveNotifications}
		<li><p><em> E.g. "course requested" emails.</em></p></li>
		{/if}
	</ul>

</fieldset>
{/if}
{if (!$newAccount && $account->roles->has($studentRole)) && ($authZ->hasPermission($viewer, 'admin') || $authZ->hasPermission($viewer, 'account manage'))}
<fieldset class="field">
	<legend>Missed reservation</legend>
	<ul class="list-group">
		<li>
			<label for="missed-reservation">
			<input type="checkbox" name="missedreservation" id="missed-reservation" 
			{if $account->missedReservation}checked aria-checked="true"{else}aria-checked="false"{/if} />
			Missed</label>
		</li>
		<li>Checked automatically for students that have missed a reservation. If left checked and the student misses another reservation, all of their future reservations will be cancelled and they will be notified via email.</li>
	</ul>
</fieldset>
{/if}
{if $pAdmin && (!$newAccount && $account->roles->has($studentRole))}
<fieldset class="field">
	<legend>Edit observation time</legend>
	<ul class="list-group">
		<li>
			<a class="" href="admin/observations/{$account->id}/all"><span class="
glyphicon glyphicon-time"> </span> Edit this users observation times</a>
		</li>
	</ul>
</fieldset>
{/if}




