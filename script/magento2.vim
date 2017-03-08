let g:magento_xsd_map = {}

sign define MagentoXmlWarning text=>> texthl=ErrorMsg linehl=ErrorMsg
sign define MagentoCsError text=>> texthl=ErrorMsg linehl=ErrorMsg
sign define MagentoCsWarning text=>> texthl=SyntasticWarningSign linehl=SyntasticWarningLine

fu! Magento2Init(homePath)
  " load local config file
  execute 'source '.fnameescape(a:homePath)

  " xsd support
  let g:MAGENTO_CONFIG_DIR = fnamemodify('./www/.vimconfig/variables.vim', ':p:h')
  let g:MAGENTO_DIR = fnamemodify(g:MAGENTO_CONFIG_DIR, ':h')

  if filereadable(g:MAGENTO_CONFIG_DIR.'/xsd/namespaces.map')
    execute "let g:magento_xsd_map = " . readfile(g:MAGENTO_CONFIG_DIR.'/xsd/namespaces.map')[0]
  endif

  augroup Magento2Auto
    autocmd!

    " xsd
    autocmd BufWritePost * call Magento2ValidateXml()
    autocmd BufReadPost * call Magento2AutocompleteXml()

    " phpcs support
    autocmd BufWritePost * call Magento2ValidateCs()
  augroup END

  " tmux & tests
  nmap <Leader>rt :call Magento2RunTest()<CR>
  nmap <Leader>ts :call Magento2TestSummary()<CR>

endfun

fu! Magento2MapDir(originalPath)
  let b:map = items(g:PATH_MAP)
  let resultPath = a:originalPath

  for row in b:map
    let resultPath = substitute(resultPath, row[0], row[1], 'g')
  endfor

  return resultPath
endfun

" initialize module
let b:magentoConfigPaths = split(globpath('.', '**/.*/variables.vim'), '\n')

if len(b:magentoConfigPaths) > 0
 call Magento2Init(b:magentoConfigPaths[0])
endif

" phpcs
fu! Magento2ValidateCs()
  let b:extension = expand('%:e')

  if b:extension != "php"
      return 1
  endif

  set shell=bash
  let b:cmd = g:PHP_PATH." ".Magento2MapDir(g:MAGENTO_DIR."/vendor/bin/phpcs")." --standard=PSR2 '".Magento2MapDir(expand("%:p"))."'"
  silent let b:result = system(b:cmd)

  sign unplace *

  let b:lines = split(b:result, "\n")
  let b:number = 0
  let g:cserrors = []

  for b:line in b:lines
    let b:linen = matchstr(b:line, '^\s\+\([0-9]\+\)')

    if !empty(b:linen)
      let b:num = matchstr(b:linen, '\([0-9]\+\)')

      if b:line =~ 'ERROR'
        let b:number += 1
        call add(g:cserrors, b:line)

        exe ":sign place 3 line=".b:num." name=MagentoCsError file=" . expand("%:p")
      else

        exe ":sign place 4 line=".b:num." name=MagentoCsWarning file=" . expand("%:p")
      endif
    endif
  endfor

  let a:buffername = "csvalidationresult"

  if b:number > 0
    let b:bnr = bufwinnr(a:buffername)
    if b:bnr <= 0
      silent execute 'split ' . a:buffername
      silent execute 'resize 10'
      let b:bnr = bufwinnr(a:buffername)
    endif

    exe b:bnr . "wincmd w"
    normal VGx
    call append(0, g:cserrors)
  else
    let b:bnr = bufwinnr(a:buffername)
    if b:bnr > 0
      exe b:bnr . "wincmd w"
      silent execute "q!"
    endif
  endif
endfun


fu! Magento2RunTest()
  if expand("%:p") =~ "app/code"
    let l:parts = split(expand("%:p"), "app/code")
    let l:relative = l:parts[0]."app/code/".join(split(l:parts[1], '/')[0:1], '/').'/Test/'

    execute "silent !tmux send-keys -t bottom C-z '".g:PHP_PATH." ".Magento2MapDir(g:MAGENTO_DIR."/vendor/bin/phpunit")." -c ".Magento2MapDir(g:MAGENTO_DIR."/dev/tests/unit/phpunit.xml.dist")." ".Magento2MapDir(l:relative)."' Enter"
    redr!
  endif
endfun

fu! Magento2TestSummary()
  if expand("%:p") =~ "app/code"
    let l:parts = split(expand("%:p"), "app/code")
    let l:relative = l:parts[0]."app/code/".join(split(l:parts[1], '/')[0:1], '/').'/Test/'

    let b:cmd = g:PHP_PATH." ".Magento2MapDir(g:MAGENTO_DIR."/vendor/bin/phpunit")." --coverage-html ".Magento2MapDir(g:MAGENTO_DIR."/dev/tests/cc/")." ".Magento2MapDir(l:relative)

    execute "silent !(".b:cmd."; open ".g:MAGENTO_DIR."/dev/tests/cc/index.html) >> /dev/null &"
    redr!
  endif
endfun


fu! Magento2ValidateXml()
  let b:extension = expand('%:e')

  if b:extension != "xml"
      return 1
  endif

  let b:cmd = "xmllint --xpath \"string(//config/@*[local-name()='noNamespaceSchemaLocation'])\" ".expand("%")
  let b:xsd = system(b:cmd)

  if has_key(g:magento_xsd_map, b:xsd)
    sign unplace 2

    let b:xsdpath = get(g:magento_xsd_map, b:xsd)
    let b:result = system("xmllint --noout --schema ".b:xsdpath." ".expand('%'))

    if b:result !~ "validates"
      let b:lines = split(b:result, "\n")

      for b:line in b:lines
        let b:linen = matchstr(b:line, '\([0-9]\)')

        if !empty(b:linen)
          exe ":sign place 2 line=".b:linen." name=MagentoXmlWarning file=" . expand("%:p")

          echom b:line
        endif
      endfor
    else
      sign unplace 2
    endif
  endif
endfun

fu! Magento2AutocompleteXml()
  let b:extension = expand('%:e')

  if b:extension != "xml"
      return 1
  endif

  let b:cmd = "xmllint --xpath \"string(//config/@*[local-name()='noNamespaceSchemaLocation'])\" ".expand("%")
  let b:xsd = system(b:cmd)

  if has_key(g:magento_xsd_map, b:xsd)
    let b:hash = substitute(system("echo -n '".b:xsd."' | md5"), '\n\+$', '', '')
    let b:cmd = ":XMLns magento2".b:hash
    exe b:cmd
  endif
endfun
