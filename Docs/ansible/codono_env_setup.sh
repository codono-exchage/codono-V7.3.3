#!/bin/bash

# Update package lists
sudo apt update

# Add the PHP repository
sudo add-apt-repository ppa:ondrej/php -y

# Install Ansible
sudo apt install ansible -y

# Run your Ansible playbook
ansible-playbook codono_ansible.yml
