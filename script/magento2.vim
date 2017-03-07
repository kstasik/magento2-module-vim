"nnoremap <leader>g :call Magento2ValidateXml()<CR>
autocmd BufWritePost * call Magento2ValidateXml()
autocmd BufWritePost * call Magento2ValidateCs()
autocmd BufReadPost * call Magento2AutocompleteXml()

sign define MagentoXmlWarning text=>> texthl=ErrorMsg linehl=ErrorMsg
sign define MagentoCsError text=>> texthl=ErrorMsg linehl=ErrorMsg
sign define MagentoCsWarning text=>> texthl=SyntasticWarningSign linehl=SyntasticWarningLine

let magento_xsd_map = {}

if filereadable('www/.vimconfig/xsd/namespaces.map')
  execute "let magento_xsd_map = " . readfile('www/.vimconfig/xsd/namespaces.map')[0]
endif

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

fu! Magento2ValidateCs()
  let b:extension = expand('%:e')

  if b:extension != "php"
      return 1
  endif

  let b:cmd = "www/vendor/bin/phpcs --standard=PSR2 ".expand("%")
  let b:result = system(b:cmd)

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

  let a:buffername = "csvalidationresult.tmp"

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
