<h1>{if $new}Create a Course{else}Edit Course: <small>{$course->shortName|escape}</small>{/if}</h1>
<form action="{$smarty.server.REQUEST_URI}" method="post">
    <div class="form-group">
        <p><strong>Status: </strong> {if $course->deleted}Deleted, {/if}{if $course->active}Active{elseif $new}New/Active{else}Archived{/if}</p>
    </div>
    <hr>
    <div class="form-group">
        <label for="facet-typeId">Type of Course</label>
        <select class="form-control" name="facet[typeId]" id="facet-typeId">
            <option value="">Choose a type of course</option>
        {foreach item='type' from=$facetTypes}
            <option value="{$type->id}"{if $facet && ($facet->typeId == $type->id)} selected="selected"{/if}>{$type->name|escape}</option>
        {/foreach}
        </select>
        {if $errors.facet_type}<p class="error">{$errors.facet_type}{/if}</p>
    </div>
    <div class="form-group">
        <label for="semester">Semester:</label>        
        <select class="form-control" name="semester" id="semester">
            <option value="">Choose a semester</option>
        {foreach item='sem' from=$semesters key='index' name='days'}
            <option value="{$sem->id}"{if $course->startDate && $course->startDate == $sem->startDate} selected="selected"{/if}>{$sem->display|escape}</option>
        {/foreach}
        </select>      
        {if $errors.startDate}<p class="error">{$errors.startDate}{/if}</p>
    </div>
    <div class="form-group">
        <label for="instructor">Instructor:</label>     
        <select class="form-control" name="instructor" id="instructor">
            <option value="">Choose an instructor</option>
        {foreach item='instructor' from=$instructors}
            <option value="{$instructor->id}" {if $course->teachers[0]->id == $instructor->id}selected default{/if}>{$instructor->fullName}</option>
        {/foreach}
        </select>
    </div>
    <div class="form-group">
        <label for="course-fullName">Course Full Name</label>
        <input type="text" class="textfield form-control" name="course[fullName]" id="course-fullName" value="{$course->fullName|escape}" />
        {if $errors.fullName}<p class="error">{$errors.fullName}{/if}</p>
    </div>
    <div class="form-group">
        <label for="course-shortName">Course Short Name</label>
        <input type="text" class="textfield form-control" name="course[shortName]" id="course-shortName" value="{$course->shortName|escape}" />
        {if $errors.shortName}<p class="error">{$errors.shortName}{/if}</p>
    </div>
    <div class="form-group">
        <label for="department">Department</label>
        <input type="text" class="textfield form-control" name="course[department]" id="department" value="{$course->department|escape}" />
    </div>
    <div class="form-group">
        <label for="facet-description">Description</label>
        <textarea class="form-control" rows="3" cols="70" name="facet[description]" id="facet-description">{$facet->description|escape}</textarea>
    </div>
    <div class="form-group">
        <label>For the purposes of this assignment, will your students have to:</label>
        {foreach item="task" key="taskId" from=$facet->GetAllTasks()}
            {assign var=match value=false}
            {foreach from=$facet->tasks item=courseTask}
                {if $task == $courseTask}{assign var=match value=true}{/if}
            {/foreach}
            <div class="checkbox">
            <label for="facet-tasks-{$taskId}">
              <input type="checkbox" name="facet[tasks][{$taskId}]" id="facet-tasks-{$taskId}" value="{$task}" {if $match}checked="checked"{/if} /> {$task}
            </label>
            </div>
        {/foreach}
    </div>
    <div class="form-group">
    {if $course->inDataSource}
        <h3>Students in the class <small class="label label-success">{$course->students|@count} total</small></h4>
        <table class="table table-striped table-condensed table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Last login</th>
                </tr>
            </thead>
        {foreach item='student' from=$course->students}
            <tr>
                <td><a class="" href="admin/accounts/{$student->id}?returnTo={$smarty.server.REQUEST_URI}">{$student->fullName}</a></td>
                <td>{if !$student->lastLoginDate}--{else}{$student->lastLoginDate->format('M j, Y h:ia')}{/if}</td>
            </tr>
        {foreachelse}
        There are no students in this course.
        {/foreach}
        </table>
    {/if}
    </div>

    <hr>
    <div class="commands">
        {generate_form_post_key}
        <input class="btn btn-primary" type="submit" name="command[save]" value="{if $new}Create{else}Save{/if} Course" />
        <a class="btn btn-default" href="admin/courses">Cancel</a>
    </div>
</form>
<!-- <br> -->
<form method="post" action="admin/courses">
    <div class="form-group pull-right">
    {if !$new}
        <input type="hidden" name="courses[{$course->id}]" value="{$course->id}" />
        <input class="btn btn-info" type="submit" name="command[active]" value="{if !$course->active && !$new}Activate{elseif !$new}Archive{/if} Course" />
    {generate_form_post_key}
    {/if}
    </div>
</form>
