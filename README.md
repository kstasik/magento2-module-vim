# magento2-module-vim
Vim module for magento2

```
{
   ...
   
    "require-dev": {
        ....
        
        "goetas-webservices/xsd-reader": "dev-bugfix",
        "kstasik/magento2-module-vim": "dev-master"
    },
    "repositories": [
       ...
        {
          "type": "vcs",
          "url": "https://github.com/kstasik/magento2-module-vim"
        },
        {
            "type": "vcs",
            "url": "https://github.com/kstasik/xsd-reader"
        }
    ]
}
```

```
docker exec -it magento2_phpfpmd_1 php /var/www/bin/magento dev:vim:generate-config --real-path=`pwd`'/www' --vim-runtime=`vim -e -T dumb --cmd 'exe "set t_cm=\<C-M>"|echo $VIMRUNTIME|quit' | tr -d '\015' `
```
