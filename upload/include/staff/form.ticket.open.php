<form action="tickets.php?a=open" method="post" class="save"  enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="create">
 <input type="hidden" name="a" value="open">
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="pull-left flush-left">
        <h2><?php echo __('Open a New Ticket');?></h2>
    </div>
</div>
 <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
    <!-- This looks empty - but beware, with fixed table layout, the user
         agent will usually only consult the cells in the first row to
         construct the column widths of the entire toable. Therefore, the
         first row needs to have two cells -->
        <tr><td style="padding:0;"></td><td style="padding:0;"></td></tr>
    </thead>
    <tbody style="display: none;">
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('User and Collaborators'); ?></strong>: </em>
                <div class="error"><?php echo $errors['user']; ?></div>
            </th>
        </tr>
        <tr>
          <td>
            <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
              <?php
              if ($user) { ?>
                  <tr><td><?php echo __('User'); ?>:</td><td>
                    <div id="user-info">
                      <input type="hidden" name="uid" id="uid" value="<?php echo $user->getId(); ?>" />
                      <a href="#" onclick="javascript:
                      $.userLookup('ajax.php/users/<?php echo $user->getId(); ?>/edit',
                      function (user) {
                        $('#user-name').text(user.name);
                        $('#user-email').text(user.email);
                      });
                      return false;
                      "><i class="icon-user"></i>
                      <span id="user-name"><?php echo Format::htmlchars($user->getName()); ?></span>
                      &lt;<span id="user-email"><?php echo $user->getEmail(); ?></span>&gt;
                    </a>
                    <a class="inline button" style="overflow:inherit" href="#"
                    onclick="javascript:
                    $.userLookup('ajax.php/users/select/'+$('input#uid').val(),
                    function(user) {
                      $('input#uid').val(user.id);
                      $('#user-name').text(user.name);
                      $('#user-email').text('<'+user.email+'>');
                    });
                    return false;
                    "><i class="icon-retweet"></i> <?php echo __('Change'); ?></a>
                  </div>
                </td>
              </tr>
              <?php
            } else { //Fallback: Just ask for email and name
              ?>
              <tr id="userRow">
                <td width="120"><?php echo __('User'); ?>:</td>
                <td>
                  <span>
                    <select class="userSelection" name="name" id="user-name"
                    data-placeholder="<?php echo __('Select User'); ?>">
                  </select>
                </span>

                <a class="inline button" style="overflow:inherit" href="#"
                onclick="javascript:
                $.userLookup('ajax.php/users/lookup/form', function (user) {
                  var newUser = new Option(user.email + ' - ' + user.name, user.id, true, true);
                  return $(&quot;#user-name&quot;).append(newUser).trigger('change');
                });
                return false;
                "><i class="icon-plus"></i> <?php echo __('Add New'); ?></a>

                <span class="error">*</span>
                <br/><span class="error"><?php echo $errors['name']; ?></span>
              </td>
              <div>
                <input type="hidden" size=45 name="email" id="user-email" class="attached"
                placeholder="<?php echo __('User Email'); ?>"
                autocomplete="off" autocorrect="off" value="<?php echo $info['email']; ?>" />
              </div>
            </tr>
            <?php
          } ?>
          <tr id="ccRow">
            <td width="160"><?php echo __('Cc'); ?>:</td>
            <td>
              <span>
                <select class="collabSelections" name="ccs[]" id="cc_users_open" multiple="multiple"
                ref="tags" data-placeholder="<?php echo __('Select Contacts'); ?>">
              </select>
            </span>

            <a class="inline button" style="overflow:inherit" href="#"
            onclick="javascript:
            $.userLookup('ajax.php/users/lookup/form', function (user) {
              var newUser = new Option(user.name, user.id, true, true);
              return $(&quot;#cc_users_open&quot;).append(newUser).trigger('change');
            });
            return false;
            "><i class="icon-plus"></i> <?php echo __('Add New'); ?></a>

            <br/><span class="error"><?php echo $errors['ccs']; ?></span>
          </td>
        </tr>
        <?php
        if ($cfg->notifyONNewStaffTicket()) {
         ?>
        <tr class="no_border">
          <td>
            <?php echo __('Ticket Notice');?>:
          </td>
          <td>
            <select id="reply-to" name="reply-to">
              <option value="all"><?php echo __('Alert All'); ?></option>
              <option value="user"><?php echo __('Alert to User'); ?></option>
              <option value="none">&mdash; <?php echo __('Do Not Send Alert'); ?> &mdash;</option>
            </select>
          </td>
        </tr>
      <?php } ?>
    </table>
          </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Ticket Information and Options');?></strong>:</em>
            </th>
        </tr>

        <tr style="display: none">
            <td width="160" class="required">
                <?php echo __('Ticket Source');?>:
            </td>
            <td>
                <select name="source">
                    <?php
                    $source = $info['source'] ?: 'Phone';
                    $sources = Ticket::getSources();
                    unset($sources['Web'], $sources['API']);
                    foreach ($sources as $k => $v)
                        echo sprintf('<option value="%s" %s>%s</option>',
                                $k,
                                ($source == $k ) ? 'selected="selected"' : '',
                                $v);
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['source']; ?></font>
            </td>
        </tr>

        <tr>
            <td width="160" class="required">
                <?php echo __('Help Topic'); ?>:
            </td>
            <td>
                <select name="topicId" onchange="javascript:
                        var data = $(':input[name]', '#dynamic-form').serialize();
                        $.ajax(
                          'ajax.php/form/help-topic/' + this.value,
                          {
                            data: data,
                            dataType: 'json',
                            success: function(json) {
                                var date =  new Date().getFullYear()+'-'+(new Date().getMonth()+1)+'-'+new Date().getDate();
                              $('#dynamic-form').empty().append(json.html);
                              $(document.head).append(json.media);
                              $('#dynamic-form tr:nth-child(15) td:nth-child(2) input ').val(date);
                              $('#dynamic-form tr:nth-child(15) td:nth-child(2) input ').attr('readonly',true);;
                              $('#dynamic-form tr:nth-child(3) td:nth-child(2) input ').val('summary');
                               $('#dynamic-form tr:nth-child(4) textarea.richtext').val('demo summary');


                            }
                          });">
                    <?php
                    if ($topics=Topic::getHelpTopics(false, false, true)) {
                        if (count($topics) == 1)
                            $selected = 'selected="selected"';
                        else { ?>
                        <option value="" selected >&mdash; <?php echo __('Select Help Topic'); ?> &mdash;</option>
<?php                   }
                        $nameForm = "";
                        foreach($topics as $id =>$name) {
                            echo sprintf('<option value="%d" %s %s>%s</option>',
                                $id, ($info['topicId']==$id)?'selected="selected"':'',
                                $selected, $name);
                            $nameForm = $name;
                        }
                        if (count($topics) == 1 && !$forms) {
                            if (($T = Topic::lookup($id)))
                                $forms =  $T->getForms();
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['topicId']; ?></font>
            </td>
        </tr>

        </tbody>
        <tbody id="dynamic-form">
        <?php
            $options = array('mode' => 'create');
            foreach ($forms as $form) {
                print $form->getForm($_SESSION[':form-data'])->getMedia();
                include(STAFFINC_DIR .  'templates/dynamic-form.tmpl.php');
            }
        ?>
        </tbody>
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo _P('action-button', 'Open');?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="javascript:
        $(this.form).find('textarea.richtext')
          .redactor('draft.deleteDraft');
        window.location.href='tickets.php'; " />
</p>
</form>