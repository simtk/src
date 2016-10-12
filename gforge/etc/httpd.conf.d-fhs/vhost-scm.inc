ServerName {core/scm_host}

<IfVersion >= 2.3>
  IncludeOptional /etc/gforge/httpd.conf.d/plugin-scm*.inc
</IfVersion>
<IfVersion < 2.3>
  Include /etc/gforge/httpd.conf.d/plugin-scm*.inc
</IfVersion>
