$.i18n().load({
  en: 'i18n/en.json'
})

$('.lang-switch').click(function(e) {
  e.preventDefault();
  $.i18n().locale = $(this).data('locale');
  update_texts();
});

