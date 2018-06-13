<?php if($leadsMessage): ?>
  <div class="notice notice-success">
    <p><?php echo $leadsMessage; ?></p>
  </div>
<?php endif; ?>
<div class="wrap">
  <h1 class="wp-heading-inline">Lista de Interessados</h1>
  <?php if (current_user_can('administrator')): ?>
    <a target="_blank" href="<?php echo admin_url('admin-post.php?action=export_leads'); ?>" class="page-title-action export-button">Exportar lista</a>  
  <?php endif ?>
  <p>Abaixo está listado todos os interessados cadastrados. Para visualizar as informações basta clicar no nome de cada um deles. </p>
  <table class="wp-list-table widefat fixed striped posts">
    <thead>
      <tr>
        <td id="cb" class="manage-column column-cb check-column">
          <label class="screen-reader-text" for="cb-select-all-1">Selecionar todos</label>
          <input id="cb-select-all-1" type="checkbox">
        </td>
        <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
         <span>Nome</span>
       </th>
       <th scope="col" id="author" class="manage-column">Email</th>
       <th scope="col" id="categories" class="manage-column column-categories">Modalidade</th>
       <th scope="col" id="author" class="manage-column">Status</th>
       <?php if (current_user_can('administrator')): ?>
         <th scope="col" id="author" class="manage-column">Responsável</th>
       <?php endif; ?>
       <th scope="col" id="date" class="manage-column column-date sortable asc">Data</th>  
     </tr>
   </thead>
   <tbody id="the-list">
    <?php foreach ($row as $item) : ?>
      <tr id="post-1" class="iedit author-self level-0 post-1 type-post status-publish format-standard hentry category-sem-categoria">
        <th scope="row" class="check-column">     
          <label class="screen-reader-text" for="cb-select-1">Selecionar Olá, mundo!</label>
          <input id="cb-select-1" type="checkbox" name="post[]" value="1">
        </th>
        <td class="" data-colname="Título">
          <strong>
            <a class="row-title" href="<?php echo admin_url('admin.php?page=seguro-saude-leads&action=view&id='.$item->id) ?>" aria-label="<?php echo $item->name; ?> (Editar)"><?php echo $item->name; ?></a>
          </strong>
          <div class="row-actions">
            <span class="edit">
              <a href="<?php echo admin_url('admin.php?page=seguro-saude-leads&action=view&id='.$item->id) ?>" aria-label="Editar este item">Visualizar</a>
            </span>
          </div>
        </td>
        <td class="" data-colname="Email"><?php echo $item->email; ?></td>
        <td class="" data-colname="Modalidade"><?php echo $item->modalidade; ?></td>   
        <td class="" data-colname="Email"><?php echo $item->status; ?></td>
        <?php if (current_user_can('administrator')): ?>
          <td class=""><?php echo get_user_by( 'id', $item->responsible )->user_nicename; ?></td>
        <?php endif; ?> 
        <td class="" data-colname="Data"><?php echo date('d/m/Y', strtotime($item->created_at)); ?></td>    
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td id="cb" class="manage-column column-cb check-column">
        <label class="screen-reader-text" for="cb-select-all-1">Selecionar todos</label>
        <input id="cb-select-all-1" type="checkbox">
      </td>
      <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
       <span>Nome</span>
     </th>
     <th scope="col">Email</th>
     <th scope="col">Modalidade</th>
     <th scope="col">Status</th>
     <?php if (current_user_can('administrator')): ?>
       <th scope="col">Responsável</th>
     <?php endif; ?>
     <th scope="col">Data</th>  
   </tr>
 </tfoot>
</table>
<?php if ($totalLeads > 10): ?>
  <div class="tablenav">
    <div class="tablenav-pages">
      <span class="displaying-num">Total de registros</span>
      <?php if ($previousPage): ?>
        <a class='prev-page disabled' title='Página Anterior' href='<?php echo admin_url("$previousPageUrl"); ?>'>&lsaquo;</a>
      <?php endif; ?>
      <span class="paging-input">
        <span class='current-page'><?php echo $currentPage; ?></span> de 
        <span class='total-pages'><?php echo $totalLeads; ?></span>
      </span>
      <?php if ($nextPage): ?>
        <a class='next-page' title='Próxima Página' href='<?php echo admin_url("$nextPageUrl"); ?>'>&rsaquo;</a>  
      <?php endif; ?>
    </div>
  </div>  
<?php endif ?>
</div>