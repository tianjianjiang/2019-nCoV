$.i18n().load({
  en: 'i18n/en.json'
})

jQuery(document).ready(function() {
  var update_texts = function() { $('body').i18n() };
  $.i18n().load({...});
  update_texts();
});

$('.lang-switch').click(function(e) {
  e.preventDefault();
  $.i18n().locale = $(this).data('locale');
  update_texts();
});

