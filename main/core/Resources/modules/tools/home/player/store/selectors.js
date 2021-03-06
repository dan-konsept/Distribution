import {createSelector} from 'reselect'

import {trans} from '#/main/app/intl/translation'
import {selectors as homeSelectors} from '#/main/core/tools/home/store/selectors'

const tabs = (state) => {
  return [].concat(homeSelectors.store(state).tabs)
    .filter(tab => !tab.restrictions || !tab.restrictions.hidden)
    .sort((a,b) => a.position - b.position)
}

const currentTab = createSelector(
  [tabs, homeSelectors.currentTabId],
  (tabs, currentTabId) => tabs.find(tab => currentTabId === tab.slug)
)

const currentTabTitle = createSelector(
  [homeSelectors.context, currentTab],
  (context, currentTab) => {
    if (currentTab) {
      return currentTab.longTitle
    }

    if (context.data && context.data.name) {
      return context.data.name
    }

    if ('desktop' === context.type) {
      return trans('desktop')
    }

    return trans('home')
  }
)

const widgets = createSelector(
  [currentTab],
  (currentTab) => currentTab ? (currentTab.widgets || []) : []
)

export const selectors = {
  tabs,
  currentTab,
  currentTabTitle,
  widgets
}
