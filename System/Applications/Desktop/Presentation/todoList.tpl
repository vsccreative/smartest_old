<div id="work-area">

  <h3>Your To-do List</h3>
  
  {if empty($todo_items)}
  
  <div class="instruction">Lucky you! There are no tasks on your to-do list at the moment.</div>
  
  {else}
  
  <ul class="todo-item" style="list-style-type:none;margin:0px;padding:0px">
    
    {foreach from=$todo_items item="todo"}
    <li style="margin-bottom:10px"><strong>{$todo.object_label}</strong><br />
      <span>Task: {$todo.description}</span><br />
      <span>Assigned By: {$todo.assigning_user.full_name}</span><br />
      <span>Status: {if $todo.is_complete}Completed{else}Awaiting completion{/if}</span><br />
      <span>{if !$todo.is_complete}{if $todo.type.autocomplete}<a href="{$domain}{$todo.action_url}">Do This Now</a>{else}<a href="{$domain}{$todo.action_url}">Go to this task</a> | <a href="{$domain}{$section}/completeTodoItem?todo_id={$todo.id}">Complete</a>{/if}{else}<a href="{$domain}{$section}/deleteTodoItem?todo_id={$todo.id}">Delete This To-do Permanently</a>{/if}</span></li>
    {/foreach}

  </ul>
  
  {/if}

</div>

<div id="actions-area">
  
  <ul class="invisible-actions-list" id="selfassigned-specific-actions" style="display:none">
    <li><strong>Selected task</strong></li>
    <li>Delete</li>
    <li>Mark as Completed</li>
  </ul>
  
  <ul class="invisible-actions-list" id="assigned-specific-actions" style="display:none">
    <li><strong>Selected task</strong></li>
    <li>Mark as Completed</li>
  </ul>
  
  <ul class="actions-list" id="non-specific-actions">
    <li><strong>Todo List Options</strong></li>
    <li>Add a new task</li>
  </ul>
  
</div>