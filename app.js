const axios = require("axios");
const cheerio = require("cheerio");
const FormData = require("form-data");
const jsonfile = require("jsonfile");
const fs = require('fs');
const { exit } = require("process");
const rawDir = './raw';

if (!fs.existsSync(rawDir)) {
  fs.mkdirSync(rawDir);
}

let __VIEWSTATE = "";
let __EVENTVALIDATION = "";
let cityCode = "";
let url = `https://ap.ece.moe.edu.tw/webecems/punishSearch.aspx`;
let result = [];
let body = "";
let cityArr = [];
let currentPage = 1;
let currentCity = '';

(async () => {
  await init();
  for (let city of cityArr) {
    if (city) {
      //console.log(city);
      await getList(city.toString().padStart(2, "0"));
    }
  }
  jsonfile.writeFile(`./file.json`, result);
})();

async function init() {
  body = (await axios.get(url)).data;
  let $ = cheerio.load(body);
  cityArr = $("select[name=ddlCityS] option")
    .map((n, obj) => $(obj).val())
    .get();
  setState();
}

function setState() {
  let $ = cheerio.load(body);
  __VIEWSTATE = $("#__VIEWSTATE").val();
  __EVENTVALIDATION = $("#__EVENTVALIDATION").val();
}

async function getList(city) {
  currentCity = city;
  currentPage = 1;
  cityCode = city;

  let data = new FormData();
  data.append("__VIEWSTATE", __VIEWSTATE);
  data.append("__EVENTVALIDATION", __EVENTVALIDATION);
  data.append("ddlCityS", cityCode);
  data.append("btnSearch", "搜尋");

  let config = {
    method: "post",
    url,
    headers: {
      ...data.getHeaders(),
    },
    data: data,
  };
  body = (await axios(config)).data;
  setState();
  fetchData();
  if (isNext()) {
    await nextPage();
  }
}

async function nextPage() {
  let data = new FormData();
  data.append("__VIEWSTATE", __VIEWSTATE);
  data.append("__EVENTVALIDATION", __EVENTVALIDATION);
  data.append("__EVENTTARGET", "PageControl1$lbNextPage");

  let config = {
    method: "post",
    url,
    headers: {
      ...data.getHeaders(),
    },
    data: data,
  };
  body = (await axios(config)).data;
  setState();
  fetchData();
  if (isNext()) {
    ++currentPage;
    await nextPage();
  }
}

function fetchData() {
  let $ = cheerio.load(body);
  fs.writeFile(rawDir + '/' + currentCity + '_' + currentPage + '.html', body, function(err) {
    if(err) {
      console.log(err);
    }
  });
  
  let newData = $(".kdCard-txt")
    .map((n, obj) => {
      let recordId = $(obj).find(".icon-map").attr("onclick").split("=")[5].split("','")[0];
      return {
        id: recordId,
        school: $(obj).find("h4").text(),
        url:
          "https://ap.ece.moe.edu.tw/webecems/dtl/punish_view.aspx?sch=" + recordId,
        city: $(obj).find('span').eq(1).text(),
        area: $(obj).find('span').eq(2).text(),
        pubType: $(obj).find('span').eq(3).text(),
        address: $(obj).find('a').eq(0).text(),
        telephone: $(obj).find('span').eq(4).text(),
        count_children: $(obj).find('span').eq(5).text(),
        status: $(obj).find('span').eq(6).text()
      };
    })
    .get();
  result = result.concat(newData);
}
function isNext() {
  let $ = cheerio.load(body);
  if ($("#PageControl1_lbNextPage").length == 0) return false;
  return !$("#PageControl1_lbNextPage").hasClass("aspNetDisabled");
}
