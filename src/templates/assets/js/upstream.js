function getTableDefaultOptions() {
    return {
        dom: "Bfrtip",
        buttons: [
            {
                text: upstream.langs['LB_COPY'],
                extend: "copy",
                className: "btn-sm"
            },
            {
                text: upstream.langs['LB_CSV'],
                extend: "csv",
                className: "btn-sm"
            },
        ],
        responsive: true,
        paging: false,
        info: false,
        columnDefs: [
            {
                orderable: false,
                searchable: false
            }
        ],
        language: {
            emptyTable: upstream.langs['MSG_TABLE_NO_DATA_FOUND'],
            search: upstream.langs['LB_SEARCH']
        }
    };
}

var milestonesTableOptions = getTableDefaultOptions();
milestonesTableOptions.columnDefs[0].targets = (function() {
    return [(jQuery('#milestones thead tr th').length - 1)];
})();
milestonesTableOptions.language.emptyTable = upstream.langs.MSG_NO_MILESTONES_YET;
var tableMilestones = jQuery('#milestones').DataTable(milestonesTableOptions);

var tasksTableOptions = getTableDefaultOptions();
tasksTableOptions.language.emptyTable = upstream.langs.MSG_NO_TASKS_YET;
tasksTableOptions.columnDefs[0].targets = (function() {
    return [(jQuery('#tasks thead tr th').length - 1)];
})();
var tableTasks = jQuery('#tasks').DataTable(tasksTableOptions);
var tableMyTasks = jQuery('#my-tasks').DataTable(tasksTableOptions);

var bugsTableOptions = getTableDefaultOptions();
bugsTableOptions.columnDefs[0].targets = (function() {
    var lastIndex = jQuery('#bugs thead tr th').length;
    return [lastIndex - 1, lastIndex - 2];
})();
bugsTableOptions.language.emptyTable = upstream.langs.MSG_NO_BUGS_YET;
var tableBugs = jQuery('#bugs').DataTable(bugsTableOptions);
var tableMyBugs = jQuery('#my-bugs').DataTable(bugsTableOptions);

var filesTableOptions = getTableDefaultOptions();
filesTableOptions.columnDefs[0].targets = (function() {
    var lastIndex = jQuery('#files thead tr th').length;
    return [lastIndex - 1, lastIndex - 2];
})();
filesTableOptions.language.emptyTable = upstream.langs.MSG_NO_FILES_YET;
var tableFiles = jQuery('#files').DataTable(filesTableOptions);

/**
 * Resize function without multiple trigger
 *
 * Usage:
 * $(window).smartresize(function(){
 *     // code here
 * });
 */
(function($,sr){
    // debouncing function from John Hann
    // http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
    var debounce = function (func, threshold, execAsap) {
      var timeout;

        return function debounced () {
            var obj = this, args = arguments;
            function delayed () {
                if (!execAsap)
                    func.apply(obj, args);
                timeout = null;
            }

            if (timeout)
                clearTimeout(timeout);
            else if (execAsap)
                func.apply(obj, args);

            timeout = setTimeout(delayed, threshold || 100);
        };
    };

    // smartresize
    jQuery.fn[sr] = function(fn){  return fn ? this.bind('resize', debounce(fn)) : this.trigger(sr); };

})(jQuery,'smartresize');


// Sidebar
jQuery(document).ready(function($){
    $('[data-toggle="tooltip"]').tooltip();

    // TODO: This is some kind of easy fix, maybe we can improve this
    var setContentHeight = function () {
        // reset height
        $('.right_col').css('min-height', $(window).height());

        var bodyHeight = $('body').outerHeight(),
            footerHeight = $('body').hasClass('footer_fixed') ? -10 : $('footer').height(),
            leftColHeight = $('.left_col').eq(1).height() + $('.sidebar-footer').height(),
            contentHeight = bodyHeight < leftColHeight ? leftColHeight : bodyHeight;

        // normalize content
        contentHeight -= $('.nav_menu').height() + footerHeight;

        $('.right_col').css('min-height', contentHeight);
    };

    $('#sidebar-menu').find('a').on('click', function(ev) {

        var $li = $(this).parent();

        if ($li.is('.active')) {
            $li.removeClass('active active-sm');
            $('ul:first', $li).slideUp(function() {
                setContentHeight();
            });
        } else {
            // prevent closing menu if we are on child menu
            if (!$li.parent().is('.child_menu')) {
                $('#sidebar-menu').find('li').removeClass('active active-sm');
                $('#sidebar-menu').find('li ul').slideUp();
            }

            $li.addClass('active');

            $('ul:first', $li).slideDown(function() {
                setContentHeight();
            });
        }
    });

    // toggle small or large menu
    $('#menu_toggle').on('click', function() {
        if ($('body').hasClass('nav-md')) {
            $('#sidebar-menu').find('li.active ul').hide();
            $('#sidebar-menu').find('li.active').addClass('active-sm').removeClass('active');
        } else {
            $('#sidebar-menu').find('li.active-sm ul').show();
            $('#sidebar-menu').find('li.active-sm').addClass('active').removeClass('active-sm');
        }

        $('body').toggleClass('nav-md nav-sm');

        setContentHeight();
    });

    // check active menu
    $('#sidebar-menu').find('a[href="' + window.location.href.split('?')[0] + '"]').parent('li').addClass('current-page');

    $('#sidebar-menu').find('a').filter(function () {
        return this.href == window.location.href.split('?')[0];
    }).parent('li').addClass('current-page').parents('ul').slideDown(function() {
        setContentHeight();
    }).parent().addClass('active');

    // recompute content when resizing
    $(window).smartresize(function(){
        setContentHeight();
    });

    setContentHeight();

    // fixed sidebar
    if ($.fn.mCustomScrollbar) {
        $('.menu_fixed').mCustomScrollbar({
            autoHideScrollbar: true,
            theme: 'minimal',
            mouseWheel:{ preventDefault: true }
        });
    }
});
// /Sidebar


// Panel toolbox
jQuery(document).ready(function($){
    $('.collapse-link').on('click', function() {
        var $BOX_PANEL = $(this).closest('.x_panel'),
            $ICON = $(this).find('i'),
            $BOX_CONTENT = $BOX_PANEL.find('.x_content');

        // fix for some div with hardcoded fix class
        if ($BOX_PANEL.attr('style')) {
            $BOX_CONTENT.slideToggle(200, function(){
                $BOX_PANEL.removeAttr('style');
            });
        } else {
            $BOX_CONTENT.slideToggle(200);
            $BOX_PANEL.css('height', 'auto');
        }

        $ICON.toggleClass('fa-chevron-up fa-chevron-down');
    });

    $('.close-link').click(function () {
        var $BOX_PANEL = $(this).closest('.x_panel');

        $BOX_PANEL.remove();
    });
});

// Instantiate NProgress lib.
(function(window, document, $, NProgress, undefined) {
  if (!NProgress) return;

  NProgress.start();

  $(window).load(function() {
    NProgress.done();
  });
})(window, window.document, jQuery, NProgress || null);






(function(window, document, $, undefined) {
  $(document).ready(function() {
    $('.c-discussion').on('click', '.o-comment[data-id] a[data-action="comment.go_to_reply"]', function(e) {
      e.preventDefault();

      var targetComment = $($(this).attr('href'));
      if (targetComment.length === 0) {
        console.error('Comment not found.');
        return;
      }

      var wrapper = $(targetComment.parents('.c-discussion'));

      wrapper.animate({
        scrollTop: targetComment.get(0).offsetTop,
      }, function() {
        targetComment.addClass('s-highlighted');
        setTimeout(function() {
          targetComment.removeClass('s-highlighted');
        }, 750);
      });
    });
  });
})(window, window.document, jQuery || {});
