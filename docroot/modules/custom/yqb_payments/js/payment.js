(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.yqb_payments = {
    attach: function (context, settings) {
      $('#edit-actions').on('click', function(e){
        e.preventDefault();
        return false;
      });
    }
  };
})(jQuery, Drupal);




// (function ($, Drupal) {
//   'use strict';
//
//   Drupal.behaviors.yqb_payments = {
//     attach: function (context, settings) {
//       $('#edit-submit').on('click', function(e){
//         e.preventDefault();
//       });
//     }
//   };
//
// })(jQuery, Drupal);

//
// jQuery(document).ready(function($){
//   $("#edit-actions").attr("disabled", true);
//
//   var monerisDataInput  = false;
//   var monerisExpInput   = false;
//   var monerisCVDInput   = false;
//
//   function validateInput(e){
//     var hook = $(e.currentTarget);
//
//     if(hook.attr("id") == "monerisDataInput"){
//       hook.val().match("^[0-9]{16}$") ? monerisDataInput = true : monerisDataInput = false;
//     }else if (hook.attr("id") == "monerisExpInput"){
//       hook.val().match("^[0-9]{4}$") ? monerisExpInput = true : monerisExpInput = false;
//     }else if (hook.attr("id") == "monerisCVDInput"){
//       hook.val().match("^[0-9]{3}$") ? monerisCVDInput = true : monerisCVDInput = false;
//     }
//
//     if(monerisDataInput && monerisExpInput &&  monerisCVDInput){
//       $("#edit-actions").attr('disabled', false);
//     }else {
//       $("#edit-actions").attr('disabled', true);
//     }
//   };
//
//   $("#monerisDataInput").on('focusout', function(e){
//     validateInput(e);
//   });
//   $("#monerisExpInput").on('focusout', function(e){
//     validateInput(e);
//   });
//   $("#monerisCVDInput").on('focusout', function(e){
//     validateInput(e);
//   });
//
//   $("#edit-actions").on("click",function(e){
//     e.preventDefault();
//     if(monerisDataInput && monerisExpInput &&  monerisCVDInput){
//       $(e.currentTarget).attr('disabled', true);
//       $(e.currentTarget).submit();
//     }
//   });
//
//
// });
//
