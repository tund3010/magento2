#! /bin/bash

# Prefix convention BEGIN
# f_: function
# vg_: global variable
# vl_: local variable
# Prefix convention END

vg_SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source "$vg_SCRIPT_DIR/lib.trap.sh"

vg_log_folder=/var/log/magento2-installer
vg_log_file="$vg_log_folder/install.log"

f_log_init() {
    if [ ! -d "$vg_log_folder" ]; then
        mkdir "$vg_log_folder"
    fi
}

f_log_write() {
    vl_dt=$(date '+%Y-%m-%d %T.%3N')
    vl_log_content="$vl_dt $@"
    echo $vl_log_content
    echo $vl_log_content >> $vg_log_file
}

f_dependency_install_Composer() {
    f_log_write Install Composer
    apt-get install curl -y
    apt-get install php-curl -y
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    composer self-update
    composer -v
}

f_dependency_install_Elasticsearch() {
    f_log_write Install Elasticsearch
    curl -fsSL https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo gpg --dearmor -o /usr/share/keyrings/elastic.gpg
    echo "deb [signed-by=/usr/share/keyrings/elastic.gpg] https://artifacts.elastic.co/packages/7.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-7.x.list
    sudo apt update -y
    sudo apt install elasticsearch -y
    #https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-elasticsearch-on-ubuntu-22-04
    #sudo nano /etc/elasticsearch/elasticsearch.yml
    sudo systemctl enable elasticsearch
    sudo systemctl start elasticsearch
}

f_dependency_install_Nginx() {
    f_log_write Install Nginx
    apt update -y
    apt install nginx -y
}

f_dependency_install_PHP() {
    f_log_write Install PHP
    # Add Ondrej's PPA
    add-apt-repository ppa:ondrej/php -y
    apt update -y

    # Install new PHP 8.3 packages
    apt install php8.3 php8.3-cli php8.3-{bz2,curl,mbstring,intl} -y

    apt install php-fpm php-mysql -y
}

f_dependency_install_MySQL() {
    f_log_write Install MySQL
    apt update -y
    apt install mysql-server -y
    mysql_secure_installation
}

f_dependencies_install() {
    f_log_write Install dependencies
    f_dependency_install_Nginx
    f_dependency_install_MySQL
    f_dependency_install_PHP
    f_dependency_install_Composer
    f_dependency_install_Elasticsearch
}

f_log_init
f_log_write Start Magento2 installation
f_dependencies_install

echo ""
echo "######################################"
echo Installation log is at $vg_log_file