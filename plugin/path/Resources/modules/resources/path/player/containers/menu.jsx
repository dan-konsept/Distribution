import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'

import {selectors as resourceSelectors} from '#/main/core/resource/store'
import {PlayerMenu as PlayerMenuComponent} from '#/plugin/path/resources/path/player/components/menu'
import {selectors} from '#/plugin/path/resources/path/store'

const PlayerMenu = withRouter(
  connect(
    (state) => ({
      path: resourceSelectors.path(state),
      steps: selectors.steps(state)
    })
  )(PlayerMenuComponent)
)

export {
  PlayerMenu
}
