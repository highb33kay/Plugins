jQuery(document).ready(function ($) {
  console.log("jQuery and DOM are ready.");

  // Define the posts and their respective contents
  var posts = [
    {
      id: 10416,
      type: "CE Broker",
      before: "APPLIED FOR CE CREDIT",
      after: "CE Broker: #20-1065998",
    },
    {
      id: 10439,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1065998",
    },
    {
      id: 10495,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1095240",
    },
    {
      id: 10554,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1066010",
    },
    {
      id: 10538,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1098040",
    },
    {
      id: 10566,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1066002",
    },
    {
      id: 8618,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1095240",
    },
    {
      id: 8690,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1098040",
    },
    {
      id: 8685,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker: #20-1065998",
    },
    { id: 5805, type: "PACE", before: "PACE Course ID: MC2021", after: "" },
    { id: 5790, type: "PACE", before: "PACE Course ID: EM2023", after: "" },
    { id: 4759, type: "PACE", before: "PACE Course ID: EM2023", after: "" },
    { id: 4841, type: "PACE", before: "PACE Course ID: IR2021", after: "" },
    { id: 10044, type: "PACE", before: "PACE Course ID: ME2023", after: "" },
    { id: 10061, type: "PACE", before: "PACE Course ID: EB2023", after: "" },
    { id: 10077, type: "PACE", before: "PACE Course ID: HV2023", after: "" },
    { id: 10095, type: "PACE", before: "PACE Course ID: WHIP2023", after: "" },
    { id: 10132, type: "PACE", before: "PACE Course ID: REC2023", after: "" },
    { id: 10167, type: "PACE", before: "PACE Course ID: ACU2023", after: "" },
    { id: 10190, type: "PACE", before: "PACE Course ID: EM2023", after: "" },
    { id: 10204, type: "PACE", before: "PACE Course ID: IR2021", after: "" },
    { id: 10242, type: "PACE", before: "PACE Course ID: MC2021", after: "" },
    {
      id: 7376,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1090752",
    },
    {
      id: 7561,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1066010",
    },
    {
      id: 4841,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1065442",
    },
    {
      id: 7301,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1065442",
    },
    {
      id: 5805,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1065586",
    },
    {
      id: 7267,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1065586",
    },
    {
      id: 5790,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1090752",
    },
    {
      id: 7376,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1090752",
    },
    {
      id: 7515,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1064874",
    },
    {
      id: 7569,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1064572",
    },
    {
      id: 7501,
      type: "CE Broker",
      before: "Approved in Florida",
      after: "CE Broker #: 20-1066002",
    },
  ];

  // Function to create a label
  function createLabel(content, styles, isClickable = false) {
    var label = $("<div/>").text(content).css(styles);
    if (isClickable) {
      label
        .css({ cursor: "pointer" })
        .attr("title", "Click to copy")
        .on("click", function () {
          copyToClipboard(content);
          alert("Copied: " + content); // Optional: Replace alert with a more subtle notification
        });
    }
    return label;
  }

  // Function to create a label
  function createLabel(content, styles, isClickable = false) {
    var label = $("<div/>").text(content).css(styles);
    if (isClickable && content) {
      label
        .css({ cursor: "pointer" })
        .attr("title", "Click to copy")
        .on("click", function () {
          copyAfterHash(content);
        });
    }
    return label;
  }

  // Function to copy text to clipboard
  function copyToClipboard(text) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
  }

  // Function to extract and copy the text after '#'
  function copyAfterHash(content) {
    let hashIndex = content.indexOf("#");
    if (hashIndex !== -1) {
      // Add 1 to hashIndex to start copying after the '#' character
      let textToCopy = content.substring(hashIndex + 1).trim();
      console.log(textToCopy);
      copyToClipboard(textToCopy);
      alert(textToCopy + ": Copied to Clipboard");
    } else {
      console.error('No "#" found in the text.');
    }
  }

  $.each(posts, function (index, post) {
    var selector = "body.postid-" + post.id + " h1.elementor-heading-title";
    $(selector).css({ position: "relative", "padding-top": "30px" });

    var beforeLabelStyles = {
      background: post.type === "PACE" ? "#0F75BC" : "#29B24D",
      color: "#ffffff",
      "text-transform": "uppercase",
      "font-size": "13px",
      "letter-spacing": "1px",
      padding: "5px 8px",
      position: "absolute",
      left: "0",
      top: "0",
    };

    var afterLabelStyles = {
      color: "#111111",
      "text-transform": "uppercase",
      "font-size": "13px",
      "letter-spacing": "1px",
      padding: "5px 8px",
      position: "absolute",
      left: "180px",
      top: "0",
    };

    // Always prepend the 'before' label
    $(selector).prepend(createLabel(post.before, beforeLabelStyles));

    // Append the 'after' label only if it is not empty and for CE Broker
    if (post.after && post.type === "CE Broker") {
      $(selector).append(createLabel(post.after, afterLabelStyles, true));
    }
  });
});
