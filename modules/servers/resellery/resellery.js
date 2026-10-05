//--------------------------------------------------------------
function reselleryT(key, fallback) {
    if (window.reselleryLang && window.reselleryLang[key]) {
        return window.reselleryLang[key];
    }
    return fallback || key;
}

$(document).ready(function () {
    if ($("#page_name").val() == "edit" && $("#servertype").val() == "resellery") {
        getReselleryProductGroups(true);
    }
});

function getSelectedRemoteGroupId() {
    var gid = $("select[name='packageconfigoption[1]']").val();
    if (!gid) {
        gid = $("input[name='packageconfigoption[1]']").val();
    }
    return gid || "";
}

function replaceConfigOption(index, html) {
    var name = "packageconfigoption[" + index + "]";
    var $input = $("input[name='" + name + "']");
    var $select = $("select[name='" + name + "']");
    if ($input.length) {
        $input.replaceWith(html);
    } else if ($select.length) {
        $select.replaceWith(html);
    } else {
        $("#packageconfigoption" + index + "input").prepend(html);
    }
}

function getReselleryProductGroups(thenLoadProducts) {
    var serverGroup = $("select[name=servergroup]").val();
    var currentGid = getSelectedRemoteGroupId();

    $.ajax({
        url: "../modules/servers/resellery/resellery_ajax.php",
        data: {
            action: "getReselleryProductGroups",
            serverGroupId: serverGroup,
            reselleryGid: currentGid,
        },
        type: "POST",
        dataType: "json",
    })
        .done(function (json) {
            if (json.result) {
                replaceConfigOption(1, json.result);
                if (thenLoadProducts) {
                    getReselleryPid();
                }
            } else {
                alert(json.message || reselleryT("unable_groups", "Unable to load product groups."));
            }
        })
        .fail(function () {
            alert(reselleryT("server_missing_groups", "Server or data not found while loading product groups."));
        });
}

function getReselleryPid() {
    var serverGroup = $("select[name=servergroup]").val();
    var reselleryPid = $("input[name='packageconfigoption[3]']").val();
    if (!reselleryPid) {
        reselleryPid = $("select[name='packageconfigoption[3]']").val();
    }
    var reselleryGid = getSelectedRemoteGroupId();

    $.ajax({
        url: "../modules/servers/resellery/resellery_ajax.php",
        data: {
            action: "getReselleryPid",
            serverGroupId: serverGroup,
            reselleryPid: reselleryPid,
            reselleryGid: reselleryGid,
        },
        type: "POST",
        dataType: "json",
    })
        .done(function (json) {
            if (json.result) {
                replaceConfigOption(3, json.result);
            } else {
                alert(json.message || reselleryT("unable_products", "Unable to load products."));
            }
        })
        .fail(function () {
            alert(reselleryT("server_missing", "Server or data not found."));
        });
}

function getCustomFields() {
    var serverGroup = $("select[name=servergroup]").val();
    var reselleryPid = $("input[name='packageconfigoption[3]']").val();
    if (!reselleryPid) {
        reselleryPid = $("select[name='packageconfigoption[3]']").val();
    }

    $.ajax({
        url: "../modules/servers/resellery/resellery_ajax.php",
        data: {
            action: "getReselleryCustomFields",
            serverGroupId: serverGroup,
            reselleryPid: reselleryPid,
            pid: getUrlParam("id", ""),
        },
        type: "POST",
        dataType: "json",
    })
        .done(function (json) {
            alert(json.message || (json.result === "success" ? reselleryT("done", "Done.") : reselleryT("failed", "Failed.")));
        })
        .fail(function () {
            alert(reselleryT("server_missing", "Server or data not found."));
        });
}

function getUrlVars() {
    var vars = {};
    window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
        vars[key] = value;
    });
    return vars;
}

function getUrlParam(parameter, defaultvalue) {
    var urlparameter = defaultvalue;
    if (window.location.href.indexOf(parameter) > -1) {
        urlparameter = getUrlVars()[parameter];
    }
    return urlparameter;
}
