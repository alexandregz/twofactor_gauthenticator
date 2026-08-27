if (window.rcmail) {
  rcmail.addEventListener('init', function() {
    var tab = $('<li>')
      .attr('id', 'settingstabplugintwofactor_gauthenticator')
      .addClass('listitem twofactor_gauthenticator');
    $('<a>')
      .attr('href', rcmail.env.comm_path + '&_action=plugin.twofactor_gauthenticator')
      .html(rcmail.gettext('twofactor_gauthenticator', 'twofactor_gauthenticator'))
      .attr('role', 'button')
      .attr('tabindex', '0')
      .attr('aria-disabled', 'false')
      .appendTo(tab);

    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.twofactor_gauthenticator', function() {
      rcmail.goto_url('plugin.twofactor_gauthenticator');
    }, true);
  });
}
