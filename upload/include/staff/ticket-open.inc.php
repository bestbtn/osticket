<?php
if (!defined('OSTSCPINC') || !$thisstaff
        || !$thisstaff->hasPerm(Ticket::PERM_CREATE, false))
        die('Access Denied');

$info=array();
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

if ($_SESSION[':form-data'] && !$_GET['tid'])
  unset($_SESSION[':form-data']);

//  Use thread entry to seed the ticket
if (!$user && $_GET['tid'] && ($entry = ThreadEntry::lookup($_GET['tid']))) {
    if ($entry->getThread()->getObjectType() == 'T')
      $oldTicketId = $entry->getThread()->getObjectId();
    if ($entry->getThread()->getObjectType() == 'A')
      $oldTaskId = $entry->getThread()->getObjectId();

    $_SESSION[':form-data']['message'] = Format::htmlchars($entry->getBody());
    $_SESSION[':form-data']['ticketId'] = $oldTicketId;
    $_SESSION[':form-data']['taskId'] = $oldTaskId;
    $_SESSION[':form-data']['eid'] = $entry->getId();
    $_SESSION[':form-data']['timestamp'] = $entry->getCreateDate();

    if ($entry->user_id)
       $user = User::lookup($entry->user_id);

     if (($m= TicketForm::getInstance()->getField('message'))) {
         $k = 'attach:'.$m->getId();
         unset($_SESSION[':form-data'][$k]);
        foreach ($entry->getAttachments() as $a) {
          if (!$a->inline && $a->file) {
            $_SESSION[':form-data'][$k][$a->file->getId()] = $a->getFilename();
            $_SESSION[':uploadedFiles'][$a->file->getId()] = $a->getFilename();
          }
        }
     }
}

if (!$info['topicId'])
    $info['topicId'] = $cfg->getDefaultTopicId();

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F;
    }
}

if ($_POST)
    $info['duedate'] = Format::date(strtotime($info['duedate']), false, false, 'UTC');

?>


<style type="text/css">
   /* #dynamic-form tr:nth-child(15) td {
       display: none !important;
   } */
   #dynamic-form tr:nth-child(1),
   #dynamic-form tr:nth-child(2),
   #dynamic-form tr:nth-child(3),
   #dynamic-form tr:nth-child(4),
   #dynamic-form tr:nth-child(5){
       display: none;
   }

</style>

<?php require_once "form.ticket.open.php";?>
<script type="text/javascript">
$(function() {
    $('input#user-email').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/users?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            $('#uid').val(obj.id);
            $('#user-name').val(obj.name);
            $('#user-email').val(obj.email);
        },
        property: "/bin/true"
    });

   <?php
    // Popup user lookup on the initial page load (not post) if we don't have a
    // user selected
    if (!$_POST && !$user) {?>
    setTimeout(function() {
      $.userLookup('ajax.php/users/lookup/form', function (user) {
        window.location.href = window.location.href+'&uid='+user.id;
      });
    }, 100);
    <?php
    } ?>
});

$(function() {
    $('a#editorg').click( function(e) {
        e.preventDefault();
        $('div#org-profile').hide();
        $('div#org-form').fadeIn();
        return false;
     });

    $(document).on('click', 'form.org input.cancel', function (e) {
        e.preventDefault();
        $('div#org-form').hide();
        $('div#org-profile').fadeIn();
        return false;
    });

    $('.userSelection').select2({
      width: '450px',
      minimumInputLength: 3,
      ajax: {
        url: "ajax.php/users/local",
        dataType: 'json',
        data: function (params) {
          return {
            q: params.term,
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (item) {
              return {
                text: item.email + ' - ' + item.name,
                slug: item.slug,
                email: item.email,
                id: item.id
              }
            })
          };
          $('#user-email').val(item.name);
        }
      }
    });

    $('.userSelection').on('select2:select', function (e) {
      var data = e.params.data;
      $('#user-email').val(data.email);
    });

    $('.userSelection').on("change", function (e) {
      var data = $('.userSelection').select2('data');
      var data = data[0].text;
      var email = data.substr(0,data.indexOf(' '));
      $('#user-email').val(data.substr(0,data.indexOf(' ')));
     });

    $('.collabSelections').select2({
      width: '450px',
      minimumInputLength: 3,
      ajax: {
        url: "ajax.php/users/local",
        dataType: 'json',
        data: function (params) {
          return {
            q: params.term,
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (item) {
              return {
                text: item.name,
                slug: item.slug,
                id: item.id
              }
            })
          };
        }
      }
    });

  });
</script>
