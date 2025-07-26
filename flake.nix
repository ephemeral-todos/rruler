{
  description = "PHP development environment";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/release-25.05";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        
        configuredPkgs = {
          php = pkgs.php.buildEnv {
            extensions = { enabled, all }: enabled ++ (with all; [ gnupg xdebug ]);
            extraConfig = "memory_limit=-1";
          };
        };
      in
      {
        devShells.default = pkgs.mkShell {
          packages = [
            configuredPkgs.php
            configuredPkgs.php.packages.composer
            configuredPkgs.php.packages.phive
            pkgs.gnupg
            pkgs.just
          ];
          shellHook = ''
            export PATH=$(pwd)/tools:$PATH
          '';
        };
      });
}
