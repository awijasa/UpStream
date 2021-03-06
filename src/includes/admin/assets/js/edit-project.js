(function($){
    function initProject() {
        var $box = $( document.getElementById( 'post-body' ) );

        var groups = [
            '#_upstream_project_milestones',
            '#_upstream_project_tasks',
            '#_upstream_project_bugs',
            '#_upstream_project_files'
        ];

        $( groups ).each( function( index, element ) {

            var $group  = $box.find( element );
            var $items  = $group.find( '.cmb-repeatable-grouping' );

            // UI stuff
            $items.addClass( 'closed' );
            hideFirstItemIfEmpty( $group );
            hideFieldWrap( $group );

            // add dynamic data into group row title
            replaceTitles( $group );
            addAvatars( $group );

            // permissions
            publishPermissions( $group );
            deletePermissions( $group );
            fileFieldPermissions( $group );

            // when we do something
            $group
                .on( 'cmb2_add_row', function( evt ) {
                    addRow( $group );
                })
                .on( 'change cmb2_add_row cmb2_shift_rows_complete', function( evt ) {
                    resetGroup( $group );
                })
                .on('click button.cmb-remove-group-row', function(evt) {
                    if ($(evt.target).hasClass('cmb-remove-group-row')) {
                        $($group).each(function(i, e) {
                            var e = $(e);
                            var e_id = e.attr('id');

                            //resetGroup(e);

                            $(groups).each( function(i, e) {
                                var $g = $box.find(e);

                                resetGroup($g);

                                if ($g.attr('id') === '_upstream_project_tasks' || $g.attr('id') === '_upstream_project_bugs') {
                                    displayEndDate($g);
                                }
                            });

                            var $m = $('#_upstream_project_milestones');
                            displayMilestoneProgress($m);
                            displayMilestoneIcon($m);

                            var $t = $('#_upstream_project_tasks');
                            displayStatusColor($t);
                            displayMilestoneIcon($t);
                            displayProgress($t);

                            displayStatusColor($('#_upstream_project_bugs'));
                        });
                    }
                })
                .on( 'keyup', titleOnKeyUp );

            // milestone specific
            if( $group.attr('id') == '_upstream_project_milestones' ) {

                displayMilestoneProgress( $group );
                displayMilestoneIcon( $group );

                $group
                    .on( 'change cmb2_add_row cmb2_shift_rows_complete', function( evt ) {
                        displayMilestoneProgress( $group );
                        displayMilestoneIcon( $group );
                    });

            }

            // task specific
            if( $group.attr('id') == '_upstream_project_tasks' ) {

                displayStatusColor( $group );
                displayMilestoneIcon( $group );
                displayProgress( $group );
                displayEndDate( $group );

                $group
                    .on( 'change cmb2_add_row cmb2_shift_rows_complete', function( evt ) {
                        displayStatusColor( $group );
                        displayMilestoneIcon( $group );
                        displayProgress( $group );
                        displayEndDate( $group );
                    });
            }

            // bug specific
            if( $group.attr('id') == '_upstream_project_bugs' ) {

                displayStatusColor( $group );
                displayEndDate( $group );

                $group
                    .on( 'change cmb2_add_row cmb2_shift_rows_complete', function( evt ) {
                        displayStatusColor( $group );
                        displayEndDate( $group );
                    });
            }

        });
    }

    function resetGroup( $group ) {
        replaceTitles( $group );
        addAvatars( $group );
    }

    /*
     * Disable 'add new' button if permissions don't allow it.
     * Used in all groups.
     */
    function publishPermissions( $group ) {
        if( ! $group.find( '.hidden' ).attr( 'data-publish' ) ) {
            $group.find( '.cmb-add-row button' ).prop( 'disabled', true ).prop( 'title', 'You do not have permission for this' );
        }
    };

    /*
     * Disable 'delete' button if permissions don't allow it.
     * Used in all groups.
     */
    function deletePermissions( $group ) {
        $group.find( '.cmb-repeatable-grouping' ).each( function() {
            var isOwner = $( this ).find( '[data-owner]' ).attr( 'data-owner' );
            if( isOwner != 'true' ) {
                $( this ).find( 'button.cmb-remove-group-row' ).prop( 'disabled', true ).prop( 'title', 'You do not have permission for this' );
            }
        });
    };

    /*
     * Disable 'upload file' button if permissions don't allow it.
     * Used in bugs and files.
     */
    function fileFieldPermissions( $group ) {
        $group.find( '.cmb-repeatable-grouping' ).each( function() {
            var file        = $( this ).find( '.cmb-type-file' );
            var disabled    = $( file ).find( '[data-disabled]' ).attr( 'data-disabled' );
            if( disabled == 'true' ) {
                $( this ).on( 'click', '.cmb-attach-list li, .cmb2-media-status .img-status img, .cmb2-media-status .file-status > span', function() {
                    return false;
                });
                $( file ).find( 'input.cmb2-upload-button' ).prop( 'disabled', true ).prop( 'title', 'You do not have permission for this' );
                $( file ).find( '.cmb2-remove-file-button' ).hide();
            }
        });
    };

    /*
     * Hides the row if there is only 1 and it is empty.
     *
     */
    function hideFirstItemIfEmpty( $group ) {
        if( $group.attr( 'id' ) == '_upstream_project_milestones' ) {
            var $items = $group.find( '.cmb-repeatable-grouping' ).first();
            $items.removeClass( 'closed' );
            return;
        }

        if( $group.find( '.hidden' ).attr( 'data-empty' ) == '1' ) {
            if( $group.find('.cmb-repeatable-grouping').length == 1 ) {
                $group.find('.cmb-repeatable-grouping').hide();
            }
        }
    };

    /*
     * Hide the field wrapping row if an input field has been hidden.
     * Via a filter such as add_filter( 'upstream_bug_metabox_fields', 'upstream_bugs_hide_field_for_role', 99, 3 );
     */
    function hideFieldWrap( $group ) {
        $group.find( 'input, textarea, select' ).each( function() {
            if( $( this ).hasClass( 'hidden' ) ) {
                $( this ).parents('.cmb-repeat-group-field').addClass('hidden');
            }
        });
    };

    /*
     * Displays the avatar in the title.
     * Used in all groups.
     */
    function addAvatars( $group ) {

        $group.find( '.cmb-repeatable-grouping' ).each( function() {
            var $this           = $( this );
            var user_assigned   = $this.find( '[data-user_assigned]' ).attr( 'data-user_assigned' );
            var user_created    = $this.find( '[data-user_created_by]' ).attr( 'data-user_created_by' );
            var av_assigned     = $this.find( '[data-avatar_assigned]' ).attr( 'data-avatar_assigned' );
            var av_created      = $this.find( '[data-avatar_created_by]' ).attr( 'data-avatar_created_by' );

            // create the boxes to hold the images first
            $this.find( 'h3 span.title' ).prepend( '<div class="av-created"></div><div class="av-assigned"></div>' );

            if( av_created ) {
                $this.find( '.av-created' ).html( '<img title="Created by: ' + user_created + '" src="' + av_created + '" height="25" width="25" />' ).show();
            } else {
                $this.find( '.av-created' ).hide();
            }

            if( av_assigned && $this.attr( 'id' ) != '_upstream_project_files' ) {
                $this.find( '.av-assigned' ).html( '<img title="Assigned to: ' + user_assigned + '" src="' + av_assigned + '" height="25" width="25" />' ).show();
            } else {
                $this.find( '.av-assigned' ).hide();
            }
        });
    };


    /*
     * Displays the title in the title.
     * Used in all groups.
     */
    function replaceTitles( $group ) {

        if( $group && $group.attr( 'id' ) == '_upstream_project_milestones' ) {

            $group.find( '.cmb-group-title' ).each( function() {
                var $this   = $( this );
                var title   = $this.next().find( '[id$="milestone"]' ).val();
                var start   = $this.next().find( '[id$="start_date"]' ).val();
                var end     = $this.next().find( '[id$="end_date"]' ).val();
                var dates   = '<div class="dates">' + start + ' - ' + end + '</div>';
                if ( title ) {
                    $this.html( '<span class="title">' + title + '</span>' + dates );
                }
            });

        } else {

            $group.find( '.cmb-group-title' ).each( function() {
                var $this       = $( this );
                var title       = $this.next().find( '[id$="title"]' ).val();
                var grouptitle  = $group.find( '[data-grouptitle]' ).data( 'grouptitle' );
                if ( ! title ) {
                    var $row        = $this.parents( '.cmb-row.cmb-repeatable-grouping' );
                    var rowindex    = $row.data( 'iterator' );
                    var newtitle    = grouptitle.replace( '{#}', ( rowindex + 1 ) );
                    $this.html( '<span class="title">' + newtitle + '</span>' );
                } else {
                    $this.html( '<span class="title">' + title + '</span>' );
                }
                if( grouptitle == 'Task {#}' )
                    displayProgress( $group );
            });

        }
    };

    function titleOnKeyUp( evt ) {
        var $group  = $( evt.target ).parents( '.cmb2-wrap.form-table' );
        replaceTitles( $group );
        addAvatars( $group );
    };

    /*
     * Displays the total milestone progress in the title.
     * Only used on the Milestones group.
     */
    function displayMilestoneProgress( $group ) {
        $group.find( '.cmb-repeatable-grouping' ).each( function() {
            var $this       = $( this );
            var title       = $this.find('.cmb-group-title .title').text();
            if( title ) {
                var progress = $('ul.milestones li .title:contains(' + title + ')').next().next().text();
            } else {
                var progress = '0';
            }
            progress = progress ? progress : '0';
            $this.find('.progress').remove();
            $this.append( '<span class="progress"><progress value="' + progress + '" max="100"></progress></span>' );
        });
    };


    /*
     * Displays the milestone icon in the title.
     * Used in tasks and bugs.
     */
    function displayMilestoneIcon( $group ) {
        $group.find( '.cmb-repeatable-grouping' ).each( function() {
            var $this       = $( this );
            var milestone   = $this.find('[id$="milestone"] option:selected').text();

            if( milestone ){
                $this.find('.on-title.dashicons').remove();
                var color   = $('ul.milestones .title:contains(' + milestone + ')').next().text();

                $this.find('button.cmb-remove-group-row.dashicons-before').after( '<span style="color: ' + color + '" class="dashicons dashicons-flag on-title"></span> ' );
            }
        });
    };

    /*
     * Displays the status in the title.
     * Used in bugs and tasks.
     */
    function displayStatusColor( $group ) {
        $group.find( '.cmb-group-title' ).each( function() {
            var $this       = $( this );
            var status      = $this.next().find( '[id$="status"] option:selected' ).text();
            if( status ){
                var $parent = $this.parents( '.cmb2-wrap.form-table' );
                color = $parent.find('ul.statuses li .status:contains(' + status + ')').next().text();
                color = color ? color : 'transparent';
                $this.append( '<span class="status" style="background: ' + color + '">' + status + '</span>' );
            }
        });
    };

    /*
     * Displays the task end date in the title.
     */
    function displayEndDate( $group ) {
        $group.find( '.cmb-group-title' ).each( function() {
            var $this       = $( this );
            var date        = $this.next().find( '[id$="end_date"], [id$="due_date"]' ).val();
            if( date ){
                $this.append( '<span class="dates">End: ' + date + '</span>' );
            }
        });
    };


    /*
     * Displays the currently selected progress in the title.
     * Only used on the Tasks group.
     */
    function displayProgress( $group ) {
        $group.find( '.cmb-repeatable-grouping' ).each( function() {
            var $this       = $( this );
            var progress    = $this.find('[id$="progress"]').val();
            progress = progress ? progress : '0';
            $this.find('.progress').remove();
            $this.append( '<span class="progress"><progress value="' + progress + '" max="100"></progress></span>' );
        });
    };

    var emptyClickEvent = function(e) {
        e.preventDefault();
        e.stopPropagation();
    };

    $(document).ready(function() {
        $('.postbox.cmb-row.cmb-repeatable-grouping[data-iterator] button.cmb-remove-group-row').each(function() {
            var self = $(this);

            if (self.attr('disabled') === 'disabled') {
                self.attr('data-disabled', 'disabled');
                self.on('click', emptyClickEvent);
            }
        });

        $('div[data-groupid]').on('click', 'button.cmb-remove-group-row', function(e) {
            var self = $(this);
            var groupWrapper = $(self.parents('div[data-groupid].cmb-nested.cmb-field-list.cmb-repeatable-group'));

            setTimeout(function() {
                $('.postbox.cmb-row.cmb-repeatable-grouping .cmb-remove-group-row[data-disabled]', groupWrapper).attr('disabled', 'disabled');
            }, 50);
        });
    });

    /*
     * When adding a new row
     *
     */
    function addRow( $group ) {
        // if first item is hidden, then show it
        var first = $group.find( '.cmb-nested .cmb-row' )[0];
        if( $(first).is(":hidden") ) {
            $(first).show();
            $(first).removeClass( 'closed' );
            $(first).next().remove();
        }

        // enable all fields in this row and reset them
        var $row = $group.find( '.cmb-repeatable-grouping' ).last();
        $row.addClass('is-new');
        $row.find( 'input, textarea, select' ).not(':button,:hidden').val("");
        $row.find( ':input' ).prop({ 'disabled': false, 'readonly': false });
        $row.find( '[data-user_assigned]' ).attr( 'data-user_assigned', '' );
        $row.find( '[data-user_created_by]' ).attr( 'data-user_created_by', '' );
        $row.find( '[data-avatar_assigned]' ).attr( 'data-avatar_assigned', '' );
        $row.find( '[data-avatar_created_by]' ).attr( 'data-avatar_created_by', '' );
        $row.find('.up-o-tab').removeClass('nav-tab-active');
        $row.find('.up-o-tab[data-target=".up-c-tab-content-data"]').addClass('nav-tab-active');

        setTimeout(function() {
          $row.find('.up-o-tab[data-target=".up-c-tab-content-comments"]').remove();
          $row.find('.up-c-tabs-header').remove();
          $row.find('.cmb-row[data-fieldtype="comments"]').remove();
        }, 25);

        $group.find( '.cmb-add-row span' ).remove();

        window.wp.autosave.server.triggerSave();

        $('.cmb-remove-group-row[data-disabled]', $row).attr('data-disabled', null);
        $('.cmb-remove-group-row[data-disabled]', $group).each(function() {
            $(this).attr('disabled', 'disabled');
        });
    }

    /*
     * Shows a clients users dynamically via AJAX
     */
    function showClientUsers() {

        var $box    = $( document.getElementById( '_upstream_project_details' ) );
        var $ul     = $box.find('.cmb2-id--upstream-project-client-users ul');

        getUsers = function( evt ) {

            var $this       = $( evt.target );
            var client_id   = $this.val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'upstream_admin_ajax_get_clients_users',
                    client_id: client_id,
                    project_id: $('#post_ID').val()
                },
                success: function(response){
                    $ul.empty();

                    if (typeof response.data === "string" && response.data) {
                        $ul.append(response.data);
                    } else if (response.data.msg) {
                        $ul.append('<li>'+ response.data.msg +'</li>');
                    }
                }
            });

            return false;

        };

        noUsers = function() {
            if( $ul.find('li').length == 0 ) {
                $ul.append('<li>No client selected</li>');
            }
        };

        noUsers();

        $box
            .on('keyup change', '#_upstream_project_client', function ( evt ) {
                getUsers( evt );
            });

    }


    // kick it all off
    initProject();
    showClientUsers();

    $('form#post').on('submit', function(e) {
        var tasksWrapper = $('#_upstream_project_tasks_repeat');
        var tasks = $('.postbox.cmb-row.cmb-repeatable-grouping', tasksWrapper);
        for (var t = 0; t < tasks.length; t++) {
            var taskWrapper = $(tasks[t]);
            if (taskWrapper.css('display') !== 'none') {
                var taskTitleField = $('input.task-title', taskWrapper);
                if (taskTitleField.val().trim().length === 0) {
                    taskTitleField.addClass('has-error');

                    $(taskTitleField.parents('.postbox.cmb-row.cmb-repeatable-grouping')).removeClass('closed');
                    $(taskTitleField.parents('.postbox.cmb2-postbox')).removeClass('closed');

                    e.preventDefault();
                    e.stopPropagation();

                    taskTitleField.focus();

                    return false;
                }
            }
        }

        $('input.task-title.has-error', tasksWrapper).removeClass('has-error');

        var wrapperMilestones = $('#_upstream_project_milestones_repeat, #_upstream_project_tasks_repeat, #_upstream_project_bugs_repeat');
        if (wrapperMilestones.length) {
            $('.postbox.cmb-row.cmb-repeatable-grouping .cmb-row *:disabled', wrapperMilestones).filter(function() {
                var self = $(this);
                if (['INPUT', 'SELECT', 'TEXTAREA'].indexOf(self.prop('tagName')) >= 0) {
                    $(this).prop({
                        'disabled': "",
                        'data-disabled': "",
                        'readonly': ""
                    });
                }
            });
        }
    });

    $('.upstream-filter').on('change', function onFilterChangeCallback(e) {
        var self = $(this);
        var targetColumn = self.data('column');

        var validColumns = ['assigned_to', 'milestone', 'status', 'severity'];
        if (validColumns.indexOf(targetColumn) >= 0) {
            var sectionWrapper = self.parents('.cmb2-metabox.cmb-field-list');
            var itemsListWrapper = $('.cmb-row.cmb-repeat-group-wrap.cmb-type-group.cmb-repeat', sectionWrapper);

            $('.no-items', itemsListWrapper).remove();

            var rows = $('.postbox.cmb-row[data-iterator]', itemsListWrapper);
            if (rows.length) {
                var newValue = this.value;
                if (newValue && newValue !== '- Show All -' && newValue !== '- Show Everyone -') {
                    var rowsLastRowIndex = rows.length - 1;
                    var itemsFound = 0;
                    rows.each(function(itemWrapperIndex, itemWrapper) {
                        var itemValue;
                        if (targetColumn === 'milestone') {
                            itemValue = $('select[name$="['+ targetColumn +']"] option:selected', itemWrapper).text();
                        } else {
                            itemValue = $('select[name$="['+ targetColumn +']"]', itemWrapper).val();
                        }

                        var displayProp = 'none';

                        if (itemValue === newValue) {
                            itemsFound++;
                            displayProp = 'block';
                        }

                        $(itemWrapper).css('display', displayProp);

                        if (itemWrapperIndex === rowsLastRowIndex) {
                            if (itemsFound === 0) {
                                var noItemsFoundWrapperHtml = $('<div class="postbox cmb-row cmb-repeatable-grouping no-items"><p>'+ self.data('no-items-found-message') +'</p></div>');
                                noItemsFoundWrapperHtml.insertBefore($('.cmb-row:not(.postbox):last-child', itemsListWrapper));
                            }
                        }
                    });
                } else {
                    rows.css('display', 'block');
                }
            }
        }
    });

    var titleHasFocus = false;
    $(document)
        .on( 'before-autosave.update-post-slug', function() {
            titleHasFocus = document.activeElement && document.activeElement.id === 'title';
        })
        .on('after-autosave.update-post-slug', function( e, data ) {
            if ( ! $('#edit-slug-box > *').length && ! titleHasFocus ) {
                $.post( ajaxurl, {
                        action: 'sample-permalink',
                        post_id: $('#post_ID').val(),
                        new_title: $('#title').val(),
                        samplepermalinknonce: $('#samplepermalinknonce').val()
                    },
                    function( data ) {
                        if ( data != '-1' ) {
                            $('#edit-slug-box').html(data);
                        }
                    }
                );
            }
        });
})(jQuery);

(function(window, document, $, upstream_project, undefined) {
  $(document).ready(function() {
    var newMessageLabel = $('#_upstream_project_discussions label[for="_upstream_project_new_message"]');
    var newMessageLabelText = newMessageLabel.text();

    function getCommentEditor(editor_id) {
      var TinyMceSingleton = window.tinyMCE ? window.tinyMCE : (window.tinymce ? window.tinymce : null);
      var theEditor = TinyMceSingleton.get(editor_id);

      return theEditor;
    }

    function getCommentEditorTextarea(editor_id) {
      return $('#' + editor_id);
    }

    function getEditorContent(editor_id, asHtml) {
      asHtml = typeof asHtml === 'undefined' ? true : (asHtml ? true : false);

      var theEditor = getCommentEditor(editor_id);
      var content = "";

      var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
      if (isEditorInVisualMode) {
        if (asHtml) {
          content = (theEditor.getContent() || "").trim();
        } else {
          content = (theEditor.getContent({ format: 'text' }) || "").trim();
        }
      } else {
        theEditor = getCommentEditorTextarea(editor_id);
        content = theEditor.val().trim();
      }

      return content;
    }

    function disableCommentArea(editor_id) {
      var theEditor = getCommentEditor(editor_id);

      if (theEditor) {
        theEditor.getDoc().designMode = 'off';

        var theEditorBody = theEditor.getBody();
        theEditorBody.setAttribute('contenteditable', 'false');
        theEditorBody.setAttribute('readonly', '1');
        theEditorBody.style.background = "#ECF0F1";
        theEditorBody.style.cursor = "progress";
      }

      var theEditorTextarea = getCommentEditorTextarea(editor_id);
      theEditorTextarea.attr('disabled', 'disabled');
      theEditorTextarea.addClass('disabled');

      $('#wp-' + editor_id + '-wrap').css('cursor', 'progress');
      $('#insert-media-button').attr('disabled', 'disabled');
      $('button[data-action^="comment."]').attr('disabled', 'disabled');
      $('button[data-action^="comments."]').attr('disabled', 'disabled');
      $('button[data-editor="'+ editor_id +'"]').attr('disabled', 'disabled');
    }

    function enableCommentArea(editor_id) {
      var theEditor = getCommentEditor(editor_id);

      if (theEditor) {
        theEditor.getDoc().designMode = 'on';

        var theEditorBody = theEditor.getBody();
        theEditorBody.setAttribute('contenteditable', 'true');
        theEditorBody.setAttribute('readonly', '0');
        theEditorBody.style.background = null;
        theEditorBody.style.cursor = null;
      }

      var theEditorTextarea = getCommentEditorTextarea(editor_id);
      theEditorTextarea.attr('disabled', null);
      theEditorTextarea.removeClass('disabled');

      $('#wp-' + editor_id + '-wrap').css('cursor', '');
      $('#insert-media-button').attr('disabled', null);
      $('button[data-action^="comment."]').attr('disabled', null);
      $('button[data-action^="comments."]').attr('disabled', null);
      $('button[data-editor="'+ editor_id +'"]').attr('disabled', null);
    }

    function resetCommentEditorContent(editor_id) {
      var theEditor = getCommentEditor(editor_id);
      if (theEditor) {
        theEditor.setContent('');
      }

      var theEditorTextarea = getCommentEditorTextarea(editor_id);
      theEditorTextarea.val('');
    }

    function appendCommentHtmlToDiscussion(commentHtml, wrapper) {
      var comment = $(commentHtml);
      comment.hide();

      commentHtml = comment.html()
        .replace(/\\'/g, "'")
        .replace(/\\"/g, '"');

      comment.html(commentHtml);

      comment.prependTo(wrapper);

      comment.slideDown();
    }

    function replyCancelButtonClickCallback(editor_id, wrapper) {
      if (editor_id === "_upstream_project_new_message") {
        $('label[for="_upstream_project_new_message"]').text(upstream_project.l.LB_ADD_NEW_COMMENT);
      }

      $('.button.u-to-be-removed', wrapper).remove();
      $('.button[data-action="comments.add_comment"]', wrapper).show();

      $('.o-comment[data-id]', wrapper).removeClass('is-mouse-over is-disabled is-being-replied');

      resetCommentEditorContent(editor_id);
    }

    function replySendButtonCallback(e) {
      e.preventDefault();

      var self = $(this);
      var parent = $(self.parent().parent());
      var commentsWrapper;

      var editor_id = $('textarea.wp-editor-area', parent).attr('id');

      if (getEditorContent(editor_id, false).length === 0) {
        setFocus(editor_id);
        return;
      }

      var commentContentHtml = getEditorContent(editor_id);

      var item_type, item_id, item_index;
      var itemsWrapper = $(self.parents('.cmb-nested.cmb-field-list[data-groupid]'));

      if (itemsWrapper.length > 0) {
        commentsWrapper = $('.c-comments', parent);
        var itemWrapper = $(self.parents('.cmb-row[data-iterator]'));

        item_index = itemWrapper.attr('data-iterator');

        var group_id = itemsWrapper.attr('data-groupid');
        var item_type_plural = group_id.replace('_upstream_project_', '');
        item_type = item_type_plural.substring(0, item_type_plural.length - 1);

        var prefix = group_id + '_' + item_index;
        item_id = $('#' + prefix + '_id').val();
      } else {
        item_type = 'project';
        commentsWrapper = $('.c-comments', self.parents('.cmb2-metabox'));
      }

      var errorCallback = function() {
        self.text(upstream_project.l.LB_SEND_REPLY);
        $('.button.u-to-be-removed', parent).attr('disabled', null);
        $('.o-comment.is-being-replied a[data-action="comment.reply"]', commentsWrapper).text(upstream_project.l.LB_REPLY);
      };

      var theCommentBeingReplied = $('.o-comment.is-being-replied', commentsWrapper);

      $.ajax({
        type: 'POST',
        url : ajaxurl,
        data: {
          action    : 'upstream:project.add_comment_reply',
          nonce     : self.data('nonce'),
          project_id: $('#post_ID').val(),
          parent_id : self.attr('data-id'),
          content   : commentContentHtml,
          item_type : item_type || null,
          item_id   : item_id || null
        },
        beforeSend: function() {
          disableCommentArea(editor_id);
          self.text(upstream_project.l.LB_REPLYING);
          $('.button.u-to-be-removed', parent).attr('disabled', 'disabled');
          $('a[data-action="comment.reply"]', theCommentBeingReplied).text(upstream_project.l.LB_REPLYING);
        },
        success: function(response) {
          if (response.error) {
            errorCallback();
            console.error(response.error);
            alert(response.error);
          } else {
            if (!response.success) {
              errorCallback();
              console.error('Something went wrong.');
            } else {
              resetCommentEditorContent(editor_id);
              replyCancelButtonClickCallback(editor_id, parent);

              appendCommentHtmlToDiscussion(response.comment_html, theCommentBeingReplied.find('.o-comment-replies').get(0));

              $('a[data-action="comment.reply"]', theCommentBeingReplied).text(upstream_project.l.LB_REPLY);
              $('.o-comment', commentsWrapper).removeClass('is-disabled is-mouse-over is-being-replied');
            }
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          errorCallback();

          var response = {
            text_status: textStatus,
            errorThrown: errorThrown
          };

          console.error(response);
        },
        complete: function() {
          enableCommentArea(editor_id);
        }
      });
    }

    function setFocus(editor_id) {
      var theEditor = getCommentEditor(editor_id);
      var isEditorInVisualMode = theEditor ? !theEditor.isHidden() : false;
      if (isEditorInVisualMode) {
        theEditor.execCommand('mceFocus', false);
      } else {
        theEditor = getCommentEditorTextarea(editor_id);
        theEditor.focus();
      }
    }

    $('label[for="_upstream_project_new_message"]').on('click', function(e) {
      e.preventDefault();

      setFocus('_upstream_project_new_message');
    });

    $('.cmb2-wrap').on('click', '.c-comments .o-comment[data-id] a[data-action="comment.reply"]', function(e) {
      e.preventDefault();

      var self = $(this);
      var commentWrapper = $(self.parents('.o-comment[data-id]').get(0));
      var comment_id = commentWrapper.attr('data-id');

      commentWrapper.addClass('is-mouse-over is-being-replied');
      var parent = $(commentWrapper.parents('.c-comments').parent());

      $('.o-comment[data-id!="'+ comment_id +'"]', parent).addClass('is-disabled');

      var editor_id = $('textarea.wp-editor-area', parent).attr('id');

      var addCommentBtn = $('.button[data-action="comments.add_comment"]', parent);
      addCommentBtn.hide();
      var controlsWrapper = $(addCommentBtn.parent());

      $('.button.u-to-be-removed', parent).remove();

      var cancelButton = $('<button></button>', {
        type : 'button',
        class: 'button button-secondary u-to-be-removed'
      })
        .text(upstream_project.l.LB_CANCEL);
      cancelButton.on('click', function(e) {
        e.preventDefault();

        replyCancelButtonClickCallback(editor_id, parent);
      });
      controlsWrapper.append(cancelButton);

      var sendButton = $('<button></button>', {
        type     : 'button',
        class    : 'button button-primary u-to-be-removed',
        'data-id': comment_id,
        'data-nonce': self.data('nonce')
      })
        .text(upstream_project.l.LB_SEND_REPLY)
        .css('margin-left', '10px');
      sendButton.on('click', replySendButtonCallback);
      controlsWrapper.append(sendButton);

      resetCommentEditorContent(editor_id);

      if (editor_id === "_upstream_project_new_message") {
        $('label[for="_upstream_project_new_message"]').text(upstream_project.l.LB_ADD_NEW_REPLY);
      }

      var finished = false;
      $('html, body').animate({
        scrollTop: $(editor_id === "_upstream_project_new_message" ? '#_upstream_project_discussions' : commentWrapper.parents('.postbox.cmb-row[data-iterator]')).offset().top
      }, {
        complete: function(e) {
          if (!finished) {
            setFocus(editor_id);
            finished = true;
          }
        }
      });
    });

    $('.cmb2-wrap').on('click', '.o-comment[data-id] a[data-action="comment.trash"]', function(e) {
      e.preventDefault();

      var self = $(this);

      var comment = $(self.parents('.o-comment[data-id]').get(0));
      if (!comment.length) {
        console.error('Comment wrapper not found.');
        return;
      }

      if (!confirm(upstream_project.l.MSG_ARE_YOU_SURE)) return;

      var errorCallback = function() {
        comment.removeClass('is-loading is-mouse-over is-being-removed');
        self.text(upstream_project.l.LB_DELETE);
      };

      $.ajax({
        type: 'POST',
        url : ajaxurl,
        data: {
          action    : 'upstream:project.trash_comment',
          nonce     : self.data('nonce'),
          project_id: $('#post_ID').val(),
          comment_id: comment.attr('data-id')
        },
        beforeSend: function() {
          comment.addClass('is-loading is-mouse-over is-being-removed');
          self.text(upstream_project.l.LB_DELETING);
        },
        success: function(response) {
          if (response.error) {
            errorCallback();

            console.error(response.error);
            alert(response.error);
          } else {
            if (!response.success) {
              console.error('Something went wrong.');

              errorCallback();
            } else {
              comment.css('background-color', '#E74C3C');

              comment.slideUp({
                complete: function() {
                  comment.remove();
                }
              });
            }
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          errorCallback();

          var response = {
            text_status: textStatus,
            errorThrown: errorThrown
          };

          console.error(response);
        }
      });
    });

    function sendNewCommentRequest(self, content, nonce, item_type, item_id, item_index, editor_id, commentsWrapper) {
      item_type = typeof item_type === 'undefined' ? 'project' : item_type;
      item_id = typeof item_id === 'undefined' ? null : item_id;
      item_index = typeof item_index === 'undefined' ? null : item_index;

      $.ajax({
        type: 'POST',
        url : ajaxurl,
        data: {
          action    : 'upstream:project.add_comment',
          nonce     : nonce,
          project_id: $('#post_ID').val(),
          item_type : item_type,
          item_index: item_index,
          item_id   : item_id,
          content   : content
        },
        beforeSend: function() {
          disableCommentArea(editor_id);
          self.text(upstream_project.l.LB_ADDING);
          self.attr('disabled', 'disabled');
        },
        success: function(response) {
          if (response.error) {
            console.error(response.error);
            alert(response.error);
          } else {
            if (!response.success) {
              console.error('Something went wrong.');
            } else {
              resetCommentEditorContent(editor_id);

              appendCommentHtmlToDiscussion(response.comment_html, commentsWrapper);
            }
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          errorCallback();

          var response = {
            text_status: textStatus,
            errorThrown: errorThrown
          };

          console.error(response);
        },
        complete: function() {
          enableCommentArea(editor_id);
          self.text(upstream_project.l.LB_ADD_COMMENT);
          self.attr('disabled', null);
        }
      });
    }

    $('#_upstream_project_discussions .button[data-action="comments.add_comment"]').on('click', function(e) {
      e.preventDefault();

      var self = $(this);

      var editor_id = '_upstream_project_new_message';
      if (getEditorContent(editor_id, false).length === 0) {
        setFocus(editor_id);
        return;
      }

      var commentHtml = getEditorContent(editor_id);

      sendNewCommentRequest(
        self,
        commentHtml,
        self.attr('data-nonce'),
        'project',
        null,
        null,
        editor_id,
        $('#_upstream_project_discussions .c-comments')
      );
    });

    function sendToggleApprovalStateRequest(self, isApproved) {
      var comment = $(self.parents('.o-comment[data-id]').get(0));
      if (!comment.length) {
        console.error('Comment wrapper not found.');
        return;
      }

      var errorCallback = function() {
        comment
          .removeClass('is-loading is-mouse-over is-being-' + (isApproved ? 'approved' : 'unapproved'))
          .css('background-color', '');
        self.text(isApproved ? upstream_project.l.LB_APPROVE : upstream_project.l.LB_UNAPPROVE);
      };

      $.ajax({
        type: 'POST',
        url : ajaxurl,
        data: {
          action    : 'upstream:project.' + (isApproved ? 'approve' : 'unapprove') + '_comment',
          nonce     : self.data('nonce'),
          project_id: $('#post_ID').val(),
          comment_id: comment.attr('data-id')
        },
        beforeSend: function() {
          comment.addClass('is-loading is-mouse-over is-being-' + (isApproved ? 'approved' : 'unapproved'));
          self.text(isApproved ? upstream_project.l.LB_APPROVING : upstream_project.l.LB_UNAPPROVING);
        },
        success: function(response) {
          if (response.error) {
            errorCallback();
            console.error(response.error);
            alert(response.error);
          } else {
            if (!response.success) {
              errorCallback();
              console.error('Something went wrong.');
            } else {
              comment.removeClass('s-status-' + (!isApproved ? 'approved' : 'unapproved') + ' is-being-' + (!isApproved ? 'approved' : 'unapproved'))
                .addClass('s-status-' + (isApproved ? 'approved' : 'unapproved'));

              var newComment = $(response.comment_html);
              var newCommentBody = $('.o-comment__body', newComment);

              $(comment.find('.o-comment__body').get(0)).replaceWith(newCommentBody);
            }
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          errorCallback();

          var response = {
            text_status: textStatus,
            errorThrown: errorThrown
          };

          console.error(response);
        },
        complete: function() {
          comment.removeClass('is-loading is-mouse-over is-being-approved is-being-unapproved');
        }
      });
    }

    $('.cmb2-wrap').on('click', '.o-comment[data-id] a[data-action="comment.unapprove"]', function(e) {
      e.preventDefault();
      sendToggleApprovalStateRequest($(this), false);
    });

    $('.cmb2-wrap').on('click', '.o-comment[data-id] a[data-action="comment.approve"]', function(e) {
      e.preventDefault();
      sendToggleApprovalStateRequest($(this), true);
    });

    $('.cmb2-wrap').on('click', '.c-comments .o-comment[data-id] a[data-action="comment.go_to_reply"]', function(e) {
      e.preventDefault();

      var targetComment = $($(this).attr('href'));
      if (!targetComment.length) {
        console.error('Comment not found.');
        return;
      }

      var wrapper = $(targetComment.parents('.c-comments'));
      var wrapperOffset = wrapper.offset();

      if (!wrapperOffset) return;

      var offset = targetComment.offset() || null;
      if (!offset) return;

      var targetCommentOffsetTop = offset.top - wrapperOffset.top;

      wrapper.animate({
        scrollTop: targetCommentOffsetTop,
      }, function() {
        targetComment.addClass('s-highlighted');
        setTimeout(function() {
          targetComment.removeClass('s-highlighted');
        }, 750);
      });
    });

    $('.cmb2-wrap').on('click', '.up-o-tab[role="tab"][data-target]', function(e) {
      e.preventDefault();

      var self = $(this);
      var wrapper = $(self.parents('.cmb-row[data-iterator]'));

      $('.up-o-tab', wrapper).removeClass('nav-tab-active');
      self.addClass('nav-tab-active');

      var target = $('.up-o-tab-content' + self.attr('data-target'), wrapper);
      if (target.length > 0) {
        $('.up-o-tab-content', wrapper).removeClass('is-active');
        target.addClass('is-active');
      }
    });

    function fetchAllComments() {
      $.ajax({
        type    : 'GET',
        url     : ajaxurl,
        data    : {
          action    : 'upstream:project.get_all_items_comments',
          nonce     : $('#project_all_items_comments_nonce').val(),
          project_id: $('#post_ID').val()
        },
        success : function(response) {
          if (response.success) {
            var itemsTypes = ['milestones', 'tasks', 'bugs', 'files'];
            for (var itemTypeIndex = 0; itemTypeIndex < itemsTypes.length; itemTypeIndex++) {
              var itemType = itemsTypes[itemTypeIndex];
              var rowset = response.data[itemType];

              $('input.hidden[type="text"][id^="_upstream_project_' + itemType + '_"][id$="_id"]').each(function() {
                var wrapper = $($(this).parents('.up-c-tabs-content'));
                if ($('up-c-tab-content-comments .c-comments', wrapper).length === 0) {
                  $('.up-c-tab-content-comments', wrapper).append($('.c-comments', wrapper));
                }
              });

              if (!rowset || rowset.length === 0) {
                continue;
              }

              for (var item_id in rowset) {
                var commentsList = rowset[item_id];
                var itemEl = $('input.hidden[type="text"][id^="_upstream_project_' + itemType + '_"][id$="_id"][value="'+ item_id +'"]');

                var wrapper = $(itemEl.parents('.up-c-tabs-content'));
                if ($('up-c-tab-content-comments .c-comments', wrapper).length === 0) {
                  $('.up-c-tab-content-comments', wrapper).append($('.c-comments', wrapper));
                }

                var commentsWrapper = $('.up-c-tab-content-comments .c-comments', wrapper);
                if (commentsList.length > 0) {
                  for (var commentIndex = 0; commentIndex < commentsList.length; commentIndex++) {
                    commentsWrapper.append($(commentsList[commentIndex]));
                  }
                }
              }
            }
          }
        },
        error   : function() {},
        complete: function() {}
      });
    }

    fetchAllComments();

    $('.cmb-row.cmb-type-comments[data-fieldtype="comments"]').each(function() {
      var self = $(this);

      var itemsWrapper = $(self.parents('.cmb-nested.cmb-field-list[data-groupid]'));
      var itemWrapper = $(self.parents('.postbox.cmb-row[data-iterator]'));

      var group_id = itemsWrapper.attr('data-groupid');

      var parent = $(self.parents('.up-c-tabs-content'));
      var wrapper = $('.up-c-tab-content-comments', parent);

      var prefix = group_id + '_' + itemWrapper.attr('data-iterator');

      var div = $('<div></div>');

      var addCommentButton = $('<button></button>', {
        'type'       : 'button',
        'class'      : 'button button-primary',
        'data-nonce' : $('#' + prefix + '_comments_add_comment_nonce', parent).val(),
        'data-action': 'comments.add_comment'
      })
        .text(upstream_project.l.LB_ADD_COMMENT)
        .on('click', function(e) {
          e.preventDefault();

          var self = $(this);

          var editor_id = prefix + '_comments_editor';
          if (getEditorContent(editor_id, false).length === 0) {
            setFocus(editor_id);
            return;
          }

          var commentHtml = getEditorContent(editor_id);

          var item_type_plural = group_id.replace('_upstream_project_', '');
          var item_id = $('#' + prefix + '_id').val();

          sendNewCommentRequest(
            self,
            commentHtml,
            self.attr('data-nonce'),
            item_type_plural.substring(0, item_type_plural.length - 1),
            item_id,
            itemWrapper.attr('data-iterator'),
            editor_id,
            $('.c-comments', itemWrapper)
          );
        });

      div.append(addCommentButton);
      wrapper.prepend(div);

      wrapper.prepend($('.cmb-td > div.wp-editor-wrap', self));
    });
  });
})(window, window.document, jQuery, upstream_project || {});
