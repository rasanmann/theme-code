(function ($) {
  $(document).on("ajaxSuccess", function() {
    $('.ajax-progress-fullscreen').remove();
  });

  Drupal.behaviors.get_faq_ajax = {

    attach: function () {
      this.addEvents();
    },

    addEvents: function () {
      let basUrl = location.protocol + "//" + location.host +"/";
      let lang = $('html').attr('lang');

      $(document).on('click touchend', '#subCategoriesFaqMenu .subCategoriesFaq__link', function (e) {
        e.preventDefault();
        let tid = $(this).data('tid')
            parentLi = $(this).parent('.subCategoryFaq__menu-item');

        if (!tid || parentLi.hasClass('active')) {
          return false;
        }
        // Icon loading
        $loadingIcon = '<div class="ajax-progress ajax-progress-fullscreen"><span class="throbber"></span></div>';
        $('#subCategoriesFaqMenu .subCategoryFaq__menu-item.active').removeClass('active')
        parentLi.addClass('active');
        $('body').append($loadingIcon);

        let href = basUrl+lang+"/faq_ajax/category/get/"+lang+"/"+ tid;
        $("#subCategoryFaqContent").load(href);
      })

      //Click on question
      $(document).on('click touchend', '#subCategoryFaqContent .faq__click-counter', function (e) {
        let _this = $(this);
        let nid = _this.data('nid')

        if (!nid || _this.hasClass('click-counted')) {
          return;
        }
        let url = basUrl+"/faq_ajax/question/click-counter/"+lang+"/"+ nid;
        _this.addClass('click-counted');
        $.ajax({
          url: url
        });
      })

        $(document).on('click', '.categoriesFaqTrigger__menu', function (e) {
          if( !$('.categoriesFaq__menu').length ) return;
          let faqMenu = $('.categoriesFaq__menu');
          faqMenu.toggleClass('opened');
            if(faqMenu.hasClass('opened')){
                $('.categoriesFaqTrigger__menu').attr({
                    'aria-expanded': true,
                    'aria-selected': true
                });
                $('.categoriesFaqTrigger__menu').attr({
                    'aria-hidden': false
                });
            } else {
              $('.categoriesFaqTrigger__menu').attr({
                'aria-expanded': false,
                'aria-selected': false,
                'aria-hidden': true
              });
            }
            e.preventDefault();
        });
        $(document).mouseup(function(e) {
            if( !$('.categoriesFaq__wrapper-menu').length && !$('.categoriesFaq__menu').length ) return;
            let container = $('.categoriesFaq__wrapper-menu');
            let faqMenu = $('.categoriesFaq__menu');
            if (!container.is(e.target) && container.has(e.target).length === 0) {
              faqMenu.removeClass('opened');
              $('.categoriesFaqTrigger__menu').attr({
                'aria-expanded': false,
                'aria-selected': false,
                'aria-hidden': true
              });
            }
        });
    },
  }
})(jQuery);
