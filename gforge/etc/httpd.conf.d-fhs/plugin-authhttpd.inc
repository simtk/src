<Location //plugins/authhttpd/post-login.php>
  # You'll need to customize the following according to your needs

  AuthType Basic
  AuthName "Forge Authentication"

  AuthUserFile /etc/gforge/authhttpd.authfile
  Require valid-user
</Location>
