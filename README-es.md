<!-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> -->

# Módulo 'Debido a los clientes'

## Características
Este módulo es desarrollado y distribuido por Laurent Bouquet para <a href="https://www.joliciel.fr" target="_new"> Joliciel </a>.

Permite generar una tabla PDF del 'Descanso debido' por los clientes.

### Usando el módulo
![Captura de pantalla Customersowedreport](img/screenshot_customersowed_steps-es.png?raw=true "Generador de PDF 'Clientes vencidos'"){imgmd}

### Ejemplo de un documento PDF generado
![Captura de pantalla Customersowedreport](img/screenshot_customersowed_pdf-es.png?raw=true "CustomersOwed"){imgmd}



## Para ayuda

Puede hacer sus preguntas en el <a href="https://www.dolibarr.fr/forum" target="_new"> Foro de Dolibarr </a>.

Para obtener ayuda con este módulo, escriba a: <a href="mailto:dolibarr@joliciel.fr"> dolibarr@joliciel.fr </a>.



## Instalación

### De archivo ZIP y GUI

- Si obtuvo el módulo en un archivo zip (como al descargar desde el mercado [Dolistore](https://www.dolistore.com)):
- haga clic en ```Inicio - Configuración - Módulos - Implementar/Instalar Plugin```
- y envíe el archivo `module_customersowed-1.x.x.zip`
- haciendo clic en el botón ```Browse``` y luego ```Send```.


Nota: Si esta pantalla le dice que no hay un directorio personalizado, verifique que su configuración sea correcta:

- En su directorio de instalación de Dolibarr, edite el archivo ```htdocs/conf/conf.php``` y verifique que las siguientes líneas no estén comentadas:
```Fil
//$ dolibarr_main_url_root_alt ...
//$ dolibarr_main_document_root_alt ...
```

- Descoméntelos si es necesario (elimine el carácter ```//` ``) y asigne la ruta de archivo correcta de acuerdo con su instalación de Dolibarr.

Por ejemplo :

- en Linux:
```Fil
$ dolibarr_main_url_root_alt = '/custom';
$ dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
```

- en Windows:
```Fil
$ dolibarr_main_url_root_alt = '/custom';
$ dolibarr_main_document_root_alt = 'C:/Mis sitios web/Dolibarr/htdocs/custom';
```

### <a name="final_steps"> </a> Paso final

En tu navegador:

  - Inicia sesión en Dolibarr como super administrador.
  - Ir a ```Inicio - Configuración - Módulos```
  - Ahora debería poder encontrar y activar el módulo 'Clientes vencidos' en la sección 'Joliciel x Les Métiers du Bâtiment'


## Licencias

### Para el código fuente

El código fuente es libremente utilizable y modificable en el respeto de la licencia
![Logotipo de GPLv3](img/gplv3.png)



### Para la documentación

Todos los textos de la documentación y los archivos README se pueden utilizar y modificar libremente en el respeto de la licencia.
![Logotipo de GFDL](img/gfdl.png)


## Para ir más lejos

Otros módulos están disponibles en <a href="https://www.dolistore.com" target="_new"> Dolistore.com </a>.

Para obtener un nuevo módulo, especialmente para usted, escriba a: <a href="mailto:dolibarr@joliciel.fr"> dolibarr@joliciel.fr </a>.