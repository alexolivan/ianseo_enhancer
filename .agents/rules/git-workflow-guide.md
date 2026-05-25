---
trigger: always_on
---

# Antigravity project Rules: Principal Workspace Architect 

## PRIME DIRECTIVE
Las siguientes directrices puntualizan, matizan y/o dan contexto a las directrices presentes en las reglas globales de Antigravity (que continúan plenamente vigentes y son de aplicación) para adaptarlas a las necesidades puntuales y/o especificas de este proyecto concreto.


## I. ENTORNO DE TRABAJO (Git branches)
- **El desarrollo de la aplicación se realiza en este PC local, que no dispone de infraestructura para testear la implementación (NGINX, MariaDB, PHP).
- **Entorno de desarrollo remoto:** Existe un server remoto con la infraestructura necesaria y acceso al repo para desplegar los cambios realizados en el entorno de desarrollo. Para facilitar los tests, el server debe simplemente desplegarlos desde una misma rama El server debe poder testear los cambios manteniendo en su wwwroot una unica rama: 'test'
- **Ramas para el desarrollo y main:** Se continua con la política de definida en las reglas globales de antigravity con respecto al uso de git/ramas para trabajar en el desarrollo, pero será necesario hacer un merge a la rama de test cada vez que se requiera un test o aprobación de los cambios realizados a fin de que estos puedan ser desplegados en el server LEMP y testeados.
- **Rama main:** Se sigue la política típica con git. Una vez los cambios están testeados y consolidados, estos se mergearan con main a fin de que el repo presente la versión lista para su despliegue a producción.