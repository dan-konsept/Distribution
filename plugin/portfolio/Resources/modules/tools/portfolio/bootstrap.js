import {bootstrap} from '#/main/app/dom/bootstrap'

import {App} from '#/plugin/portfolio/tools/portfolio'

// generate application
const portfolioApp = new App()

// mount the react application
bootstrap('.portfolio-container', portfolioApp.component, portfolioApp.store, portfolioApp.initialData)