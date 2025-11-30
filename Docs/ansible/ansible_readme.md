Alternative approach to setting up your environment and configuring extensions is to use a configuration management tool like Ansible. Ansible allows you to define your desired system state in a declarative way and automate the setup process. Here's a simplified version of how you could use Ansible for your environment for codono setup:

1. Install Ansible:
```bash
sudo apt update
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install ansible -y

```

2. Run the Ansible playbook:
```bash
ansible-playbook codono_ansible.yml
```

Please note that Ansible is a powerful tool,You might want to customize it according to your exact needs and add error handling, configurations for virtual hosts, and other settings as required. Always test your playbook in a controlled environment before using it on a production system.