import {reducer} from '#/plugin/portfolio/tools/portfolio/store'
import {PortfolioTool} from '#/plugin/portfolio/tools/portfolio/components/tool'

export const App = () => ({
  component: PortfolioTool,
  store: reducer,
  initialData: (initialData) => Object.assign({
    tool: {
      name: 'portfolio',
      currentContext: {}
    }
  }, initialData)
})