<h1>{if $viewall}All{else}My{/if} Courses
{if $pAdmin}
<div class="pull-right">
    {if $viewall}
        <a class="btn btn-sm btn-default" href="courses" role="button">View All My Courses</a>
    {else}
        <a class="btn btn-sm btn-default" href="courses?viewall=true" role="button">View All Courses</a>
    {/if}
</div>
{else}
{if $courses}
<div class="pull-right">
    {if $viewactive}
        <a class="btn btn-sm btn-default" href="courses" role="button">All Courses</a>
    {else}
        <a class="btn btn-sm btn-default" href="courses?viewactive=true" role="button">My Current Courses</a>
    {/if}
</div>
{/if}
{/if}
</h1>

{if $pendingRequests}
<div class="alert alert-info">
    <p class="lead">You have {$pendingRequests|@count} pending course request{if $pendingRequests|@count > 1}s{/if}:</p>
    <ul class="">
        {foreach from=$pendingRequests item=request}
            <li>{$request->course->facets->index(0)->shortDescription}</li>
        {/foreach}
    </ul>
    <br>
    <p class=""><small><em>You will be notified when an admin approves or denies your request.</em></small></p>
</div>
{/if}

{foreach item='course' from=$courses}
<div class="panel panel-default">
  <div class="panel-heading">
    <div class="row">
        <div class="col-xs-9">
            <h2 class="panel-title">
                <a href="courses/view/{$course->id}">{$course->fullName|escape}{if $course->shortName} <br>
                <small>{$course->shortName|escape}</small>{/if}</a>
            </h2>
        </div>
        <div class="col-xs-3 text-right">
            <span class="">{$course->semester->display}</span>
        </div>
    </div>
  </div>
  <div class="panel-body">
    {assign var='facet' value=$course->facets->index(0)}
<!--     <p class="lead"><strong>{$course->students|@count} students - </strong><small>{$facet->type->name|escape}</small></p>
    <p class="">{$facet->description|escape}</p> -->
    <dl class="dl-horizontal">
        <dt>Instructors:</dt>
        {foreach item='instructor' from=$course->teachers}
            <dd>{$instructor->firstName} {$instructor->lastName}</dd>
        {/foreach}
        <dt>Number of students:</dt>
        <dd>{$course->students|@count}</dd>
        <dt>Course type:</dt>
        <dd>{$facet->type->name|escape}</dd>
    </dl>
    <p class="">{$facet->description}</p>
  </div>
</div>
{foreachelse}
<p class="">You have no courses yet.</p>
{/foreach}
<hr>
<div class="course-actions">
    <ul class="list-unstyled">
        {if $canRequest}
        <li><a href="courses/request" class="btn btn-primary">Request a course</a></li>
        {/if}
    </ul>
</div>