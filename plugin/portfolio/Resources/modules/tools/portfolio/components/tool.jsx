import React from 'react'
import {PropTypes as T} from 'prop-types'
import {connect} from 'react-redux'

import {currentUser} from '#/main/app/security'
import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {Routes, withRouter} from '#/main/app/router'

import {makeId} from '#/main/core/scaffolding/id'
import {ToolPage} from '#/main/core/tool/containers/page'

import {constants} from '#/plugin/portfolio/tools/portfolio/constants'
import {actions} from '#/plugin/portfolio/tools/portfolio/store'
import {Portfolios} from '#/plugin/portfolio/tools/portfolio/components/portfolios'
import {Portfolio} from '#/plugin/portfolio/tools/portfolio/components/portfolio'
import {PortfolioForm} from '#/plugin/portfolio/tools/portfolio/components/portfolio-form'

const authenticatedUser = currentUser()

const Tool = (props) =>
  <ToolPage
    actions={[
      {
        name: 'new',
        type: LINK_BUTTON,
        icon: 'fa fa-fw fa-plus',
        label: trans('add_a_portfolio', {}, 'portfolio'),
        target: '/portfolio',
        primary: true
      }
    ]}
  >
    <Routes
      routes={[
        {
          path: '/',
          component: Portfolios,
          exact: true
        }, {
          path: '/portfolios/:id',
          component: Portfolio,
          onEnter: (params) => props.openForm(params.id),
          onLeave: () => props.resetForm()
        }, {
          path: '/portfolio/:id?',
          component: PortfolioForm,
          onEnter: (params) => props.openForm(params.id),
          onLeave: () => props.resetForm()
        }
      ]}
    />
  </ToolPage>

Tool.propTypes = {
  openForm: T.func.isRequired,
  resetForm: T.func.isRequired
}

const PortfolioTool = withRouter(connect(
  null,
  (dispatch) => ({
    openForm(id = null) {
      dispatch(actions.open('portfolio', id, {
        id: makeId(),
        meta: {
          visibility: constants.VISIBILITY_ME,
          owner: authenticatedUser
        }
      }))
    },
    resetForm() {
      dispatch(actions.open('portfolio', null, {}))
    }
  })
)(Tool))

export {
  PortfolioTool
}