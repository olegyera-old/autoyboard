<template>
    <div class="ytitle">
        <h2 v-if="lang==='ru'">{{title}}</h2>
        <h2 v-else>{{uatitle}}</h2>
        <div class="change_visible">
            <button
                @click="changeVisible = false"
                class="change_view "
                :class="{'active': changeVisible == false}"
            >
                <i class="fas fa-th"></i>
            </button>
            <button
                @click="changeVisible = true"
                class="change_view"
                :class="{'active' : changeVisible}"
            >
                <i class="fas fa-th-list"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default{
    props: ['title', 'lang', 'uatitle'],
    data(){
        return{
            changeVisible: false,
        }
    },
    watch: {
        changeVisible(to){
            this.$emit('getStatus', to)
            console.log(this.changeVisible)
        },
    }
}
</script>
