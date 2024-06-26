{#if loading}
  <Loading full={true} move={true}/>
{:else}
  <article class="index">
    <header class="index__header">
      <h1>{_title}</h1>
    </header>
    {#if categories?.length > 0}
      <div class="index__categories">
        <Categories
          items={_categories}
          active={route.params.category || ''}/>
      </div>
    {/if}
    <div class="index__body">
      {#if articles?.length > 0}
        <div class="index__items">
          <Items>
            {#each articles as o, key}
              <Item
                srl={o.srl}
                title={o.title}
                image={o.image}
                date={o.date}
                nest={o.nest}
                category={o.category}/>
            {/each}
          </Items>
        </div>
      {:else}
        <div class="index__empty">
          <Error status={204} message="no articles"/>
        </div>
      {/if}
      {#if totalArticles > 0}
        <div class="index__paginate">
          <Paginate
            page={route.query.page}
            total={totalArticles}
            size={size}
            url="./"
            query={_query}/>
        </div>
      {/if}
    </div>
  </article>
{/if}

<script>
import { ofetch } from 'ofetch'
import { error } from '../store'
import Error from '../components/error.svelte'
import Categories from '../components/pages/index/categories.svelte'
import Items from '../components/pages/index/items.svelte'
import Item from '../components/pages/index/item.svelte'
import Loading from '../components/loading/loading-page.svelte'
import Paginate from '../components/paginate.svelte'

export let route
let currentRoute
let loading = true
let nest = undefined
let categories = []
let totalArticles = 0
let articles = []
let size = Number(import.meta.env.VITE_INDEX_SIZE)
let title = 'Newest articles'

$: if (!route.params?.nest) updateIndex()
$: if (route.params?.nest && (currentRoute?.params?.nest !== route.params?.nest)) updateNest()
$: if (route.params?.nest && (currentRoute?.params?.category !== route.params?.category)) updateNestArticles()
$: if (route.params?.nest && currentRoute?.query.page !== route.query.page) updatePageFromNest()
$: _categories = categories.map(o => {
  return {
    srl: o.srl ? String(o.srl) : '',
    label: o.name,
    count: o.count_article,
    link: o.srl ? `/nest/${currentRoute.params?.nest}/${o.srl}/` : `/nest/${currentRoute.params?.nest}/`,
  }
})
$: _title = decodeURIComponent(title)
$: _query = filteringQuery(route.query)

function setTitle()
{
  if (nest?.title)
  {
    title = nest.title
  }
  else if (nest)
  {
    title = 'unknown nest'
  }
  else if (route?.query?.q)
  {
    title = `S:/ ${currentRoute?.query?.q}`
  }
  else
  {
    title = 'Newest articles'
  }
}

async function updateIndex()
{
  try
  {
    currentRoute = route
    loading = true
    let query = {}
    if (Number(route.query?.page) > 1)
    {
      query.page = Number(route.query?.page)
    }
    if (route.query?.q)
    {
      query.q = decodeURIComponent(route.query?.q)
    }
    setTitle()
    let res = await ofetch('/api/', {
      responseType: 'json',
      query,
    })
    nest = undefined
    categories = []
    totalArticles = res.total || 0
    articles = res.items?.length > 0 ? res.items : []
    loading = false
  }
  catch (e)
  {
    const status = e.response?._data?.status || 500
    const message = e.response?._data?.message || 'Unknown error'
    error.update(() => ({ status, message }))
    loading = false
  }
}

async function updateNest()
{
  try
  {
    currentRoute = route
    loading = true
    let query = {}
    if (Number(route.query?.page) > 1)
    {
      query.page = Number(route.query?.page)
    }
    if (currentRoute.params.category)
    {
      query.categorySrl = currentRoute.params.category
    }
    let res = await ofetch(`/api/nests/${currentRoute.params.nest}/`, {
      responseType: 'json',
      query,
    })
    nest = res.nest
    categories = res.categories
    totalArticles = res.articles?.total || 0
    articles = res.articles?.items || []
    setTitle()
    loading = false
  }
  catch (e)
  {
    const status = e.response?._data?.status || 500
    const message = e.response?._data?.message || 'Unknown error'
    error.update(() => ({ status, message }))
    loading = false
  }
}

async function updateNestArticles()
{
  try
  {
    currentRoute = route
    loading = true
    let query = {}
    if (Number(route.query?.page) > 1)
    {
      query.page = Number(route.query?.page)
    }
    query.categorySrl = currentRoute.params.category || ''
    let res = await ofetch(`/api/nests/${nest.srl}/articles/`, {
      responseType: 'json',
      query,
    })
    totalArticles = res.total || 0
    articles = res.items || []
    loading = false
  }
  catch (e)
  {
    const status = e.response?._data?.status || 500
    const message = e.response?._data?.message || 'Unknown error'
    error.update(() => ({ status, message }))
    loading = false
  }
}

async function updatePageFromNest()
{
  if (route.params.category)
  {
    await updateNestArticles()
  }
  else
  {
    await updateNest()
  }
}

function filteringQuery(src)
{
  if (src.q) src.q = decodeURI(src.q)
  return src || {}
}
</script>

<style src="./index.scss" lang="scss"></style>
