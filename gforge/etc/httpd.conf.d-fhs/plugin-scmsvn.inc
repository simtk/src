<Location /svn>
  <IfModule mod_dav_svn.c>
    DAV svn
    SVNParentPath {scmsvn/repos_path}

    <IfModule mod_authz_svn.c>
      # how to authenticate a user
      AuthzSVNAccessFile /var/lib/gforge/svnroot-access
      AuthType Basic
      AuthName "Document repository"
      AuthUserFile /var/lib/gforge/svnroot-authfile

      # only authenticated users may access the repository
      Require valid-user
    </IfModule>
    <IfModule !mod_authz_svn.c>
      # allow read-only browsing
      <LimitExcept GET PROPFIND OPTIONS REPORT>
      </LimitExcept>
    </IfModule>
  </IfModule>
</Location>
