# Comment mettre à jour le plugin

Le plugin integere un système de mise à jour basé sur [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) et est lié au dépôt git du plugin sur [wordpress-toolkit-plugin](https://git.hawai.li/hawai-li/wordpress-toolkit-plugin).

Pour mettre à jour le plugin, il faut changer la version `readme.txt` et `wordpress-toolkit-plugin.php` et pousser les changements sur le dépôt git. Le plugin detectera automatiquement les mis à jour sur les sites utilisant le plugin et proposera la mise à jour.
