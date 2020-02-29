jQuery(document).ready(function() {
  var update_texts = function() { $('body').i18n() };
  $.i18n().load({
    en: 'jquery_i18n/data/en.json'
  });
  update_texts();

  $('.lang-switch').click(function(e) {
    e.preventDefault();
    $.i18n().locale = $(this).data('locale');
    update_texts();
  });
});

